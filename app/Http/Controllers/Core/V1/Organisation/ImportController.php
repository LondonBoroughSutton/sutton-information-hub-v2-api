<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\BatchUpload\SpreadsheetParser;
use App\BatchUpload\StoresSpreadsheets;
use App\Contracts\SpreadsheetController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organisation\ImportRequest;
use App\Models\Role;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportController extends Controller implements SpreadsheetController
{
    use StoresSpreadsheets;

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
     * @param \App\Http\Requests\Organisation\ImportRequest $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ImportRequest $request)
    {
        ['rejected' => $rejected, 'imported' => $imported] = $this->processSpreadsheet($request->input('spreadsheet'));

        $responseStatus = 201;
        $response = ['imported_row_count' => $imported];

        if (count($rejected)) {
            $responseStatus = 422;
            $response = ['errors' => ['spreadsheet' => $rejected]];
        }

        return response()->json([
            'data' => $response,
        ], $responseStatus);
    }

    /**
     * Validate the spreadsheet rows.
     *
     * @param String $filePath
     * @return Array
     */
    public function validateSpreadsheet(String $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        $spreadsheetParser->readHeaders();

        $rejectedRows = [];

        foreach ($spreadsheetParser->readRows() as $i => $row) {
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
     * @param String $filePath
     */
    public function importSpreadsheet(String $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        $spreadsheetParser->readHeaders();

        $importedRows = 0;
        $adminRowBatch = [];

        DB::transaction(function () use ($spreadsheetParser, &$importedRows, &$adminRowBatch) {
            $organisationAdminRoleId = Role::organisationAdmin()->id;
            $globalAdminIds = Role::globalAdmin()->users()->pluck('users.id');
            $organisationRowBatch = $adminRowBatch = [];
            foreach ($spreadsheetParser->readRows() as $organisationRow) {
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
