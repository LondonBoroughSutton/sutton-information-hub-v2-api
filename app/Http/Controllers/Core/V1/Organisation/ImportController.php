<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\BatchUpload\SpreadsheetHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\SpreadsheetImportRequest;
use App\Models\Role;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Mime\MimeTypes;

class ImportController extends Controller
{
    /**
     * Number of rows to import at once.
     */
    const ROW_IMPORT_BATCH_SIZE = 100;

    /**
     * OrganisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\SpreadsheetImportRequest $request
     * @throws Illuminate\Validation\ValidationException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(SpreadsheetImportRequest $request)
    {
        $filePath = $this->storeBase64FileString($request->input('spreadsheet'), 'batch-upload');

        if (!Storage::disk('local')->exists($filePath) || !is_readable(Storage::disk('local')->path($filePath))) {
            throw new FileNotFoundException($filePath);
        }

        $rejectedRows = $this->validateSpreadsheet($filePath);
        $importedRows = 0;

        if (!count($rejectedRows)) {
            try {
                $importedRows = $this->importSpreadsheet($filePath);
            } catch (\Exception $e) {
                Storage::disk('local')->delete($filePath);

                abort(500, $e->getMessage());
            }
        }

        Storage::disk('local')->delete($filePath);

        $responseStatus = 201;
        $response = ['imported_row_count' => $importedRows];

        if (count($rejectedRows)) {
            $responseStatus = 422;
            $response = ['errors' => ['spreadsheet' => $rejectedRows]];
        }

        return response()->json([
            'data' => $response,
        ], $responseStatus);
    }

    /**
     * Store a binary file blob and update the models properties.
     *
     * @param string $blob
     * @param string $path
     * @param string $mime_type
     * @param string $ext
     * @return string
     */
    protected function storeBinaryUpload(string $blob, string $path, $mime_type = null, $ext = null)
    {
        $path = empty($path) ? '' : trim($path, '/') . '/';
        $mime_type = $mime_type ?? $this->getFileStringMimeType($blob);
        $ext = $ext ?? $this->guessFileExtension($mime_type);
        $filename = md5($blob) . '.' . $ext;
        Storage::disk('local')->put($path . $filename, $blob);

        return $path . $filename;
    }

    /**
     * Store a Base 64 encoded data string.
     *
     * @param string $file_data
     * @param string $path
     * @return string
     */
    protected function storeBase64FileString(string $file_data, string $path)
    {
        preg_match('/^data:(application\/[a-z\-\.]+);base64,(.*)/', $file_data, $matches);
        if (count($matches) < 3) {
            throw ValidationException::withMessages(['spreadsheet' => 'Invalid Base64 Excel data']);
        }
        if (!$file_blob = base64_decode(trim($matches[2]), true)) {
            throw ValidationException::withMessages(['spreadsheet' => 'Invalid Base64 Excel data']);
        }

        return $this->storeBinaryUpload($file_blob, $path, $matches[1]);
    }

    /**
     * Get the mime type of a binary file string.
     *
     * @var string
     * @return string mime type
     */
    protected function getFileStringMimeType(string $file_str)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $file_str);
        finfo_close($finfo);

        return $mime_type;
    }

    /**
     * Guess the extension for a file from it's mime-type.
     *
     * @param string $mime_type
     * @return string
     */
    protected function guessFileExtension(string $mime_type)
    {
        return (new MimeTypes())->getExtensions($mime_type)[0] ?? null;
    }

    /**
     * Validate the spreadsheet rows.
     *
     * @param string $filePath
     * @return array
     */
    protected function validateSpreadsheet(string $filePath)
    {
        $spreadsheetHandler = new SpreadsheetHandler();

        $spreadsheetHandler->import(Storage::disk('local')->path($filePath));

        $spreadsheetHandler->readHeaders();

        $rejectedRows = [];

        foreach ($spreadsheetHandler->readRows() as $i => $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'min:1', 'max:255'],
                'description' => ['required', 'string', 'min:1', 'max:10000'],
                'url' => ['present', 'url', 'max:255'],
                'email' => ['present', 'nullable', 'required_without:phone', 'email', 'max:255'],
                'phone' => [
                    'present',
                    'nullable',
                    'required_without:email',
                    'string',
                    'min:1',
                    'max:255',
                ],
            ]);

            if ($validator->fails()) {
                $row['index'] = $i;
                $rejectedRows[] = ['row' => $row, 'errors' => $validator->errors()];
            }
        }

        return $rejectedRows;
    }

    /**
     * Import the uploaded file contents.
     *
     * @param string $filePath
     */
    protected function importSpreadsheet(string $filePath)
    {
        $spreadsheetHandler = new SpreadsheetHandler();

        $spreadsheetHandler->import(Storage::disk('local')->path($filePath));

        $spreadsheetHandler->readHeaders();

        $importedRows = 0;
        $adminRowBatch = [];

        DB::transaction(function () use ($spreadsheetHandler, &$importedRows, &$adminRowBatch) {
            $organisationAdminRoleId = Role::organisationAdmin()->id;
            $globalAdminIds = Role::globalAdmin()->users()->pluck('users.id');
            $organisationRowBatch = $adminRowBatch = [];
            foreach ($spreadsheetHandler->readRows() as $organisationRow) {
                $organisationRow['id'] = (string) Str::uuid();
                $organisationRow['slug'] = Str::slug($organisationRow['name'] . ' ' . uniqid(), '-');
                $organisationRow['created_at'] = Date::now();
                $organisationRow['updated_at'] = Date::now();
                $organisationRowBatch[] = $organisationRow;

                foreach ($globalAdminIds as $globalAdminId) {
                    $adminRowBatch[] = [
                        'id' => (string) Str::uuid(),
                        'user_id' => $globalAdminId,
                        'role_id' => $organisationAdminRoleId,
                        'organisation_id' => $organisationRow['id'],
                        'created_at' => Date::now(),
                        'updated_at' => Date::now(),
                    ];
                }

                if (count($organisationRowBatch) === self::ROW_IMPORT_BATCH_SIZE) {
                    DB::table('organisations')->insert($organisationRowBatch);
                    DB::table('user_roles')->insert($adminRowBatch);
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                    $organisationRowBatch = $adminRowBatch = [];
                }
            }

            if (count($organisationRowBatch) && count($organisationRowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table('organisations')->insert($organisationRowBatch);
                DB::table('user_roles')->insert($adminRowBatch);
                $importedRows += count($organisationRowBatch);
            }
        }, 5);

        return $importedRows;
    }
}
