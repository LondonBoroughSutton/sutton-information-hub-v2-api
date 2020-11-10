<?php

namespace App\Http\Controllers\Core\V1\User;

use App\BatchUpload\SpreadsheetParser;
use App\BatchUpload\StoresSpreadsheets;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ImportRequest;
use App\Rules\Postcode;
use App\Rules\UkPhoneNumber;
use App\Rules\UserEmailNotTaken;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportController extends Controller
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
        $this->ignoreDuplicateIds = $request->input('ignore_duplicates', []);
        $this->processSpreadsheet($request->input('spreadsheet'));

        $responseStatus = 201;
        $response = ['imported_row_count' => $this->imported];

        if (count($this->rejected)) {
            $responseStatus = 422;
            $response['errors'] = ['spreadsheet' => $this->rejected];
        }

        if (count($this->duplicates)) {
            $responseStatus = 422;
            $response['duplicates'] = $this->duplicates;
        }

        return response()->json([
            'data' => $response,
        ], $responseStatus);
    }

    /**
     * Validate the spreadsheet rows.
     *
     * @param string $filePath
     * @return array
     */
    public function validateSpreadsheet(string $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        $spreadsheetParser->readHeaders();

        $rejectedRows = [];

        foreach ($spreadsheetParser->readRows() as $i => $row) {
            $validator = Validator::make($row, [
                'first_name' => ['required', 'string', 'min:1', 'max:255'],
                'last_name' => ['required', 'string', 'min:1', 'max:255'],
                'email' => ['required', 'email', 'max:255', new UserEmailNotTaken()],
                'phone' => ['present', 'nullable', 'string', 'min:1', 'max:255', new UkPhoneNumber()],
                'employer_name' => [
                    'present',
                    'nullable',
                    'string',
                    'min:1',
                    'max:255',
                ],
                'address_line_1' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
                'address_line_2' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
                'address_line_3' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
                'city' => ['required_with:address_line_1', 'nullable', 'string', 'min:1', 'max:255'],
                'county' => ['required_with:address_line_1', 'nullable', 'string', 'min:1', 'max:255'],
                'postcode' => ['required_with:address_line_1', 'nullable', 'string', 'min:1', 'max:255', new Postcode()],
                'country' => ['required_with:address_line_1', 'nullable', 'string', 'min:1', 'max:255'],
            ]);

            $row['index'] = $i + 2;
            if ($validator->fails()) {
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
    public function importSpreadsheet(string $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        /**
         * Load the first row of the Spreadsheet as column names.
         */
        $spreadsheetParser->readHeaders();

        $importedRows = 0;

        $userHeaders = [
            'first_name',
            'last_name',
            'password',
            'email',
            'phone',
            'employer_name',
        ];

        $addressHeaders = [
            'address_line_1',
            'address_line_2',
            'address_line_3',
            'city',
            'county',
            'postcode',
            'country',
        ];

        DB::transaction(function () use ($spreadsheetParser, &$importedRows, $userHeaders, $addressHeaders) {
            $userRowBatch = $addressRowBatch = [];
            foreach ($spreadsheetParser->readRows() as $i => $userRow) {
                /**
                 * Extract the Address data.
                 */
                $addressRow = collect($userRow)->only($addressHeaders)->all();

                /**
                 * Extract the User only data.
                 */
                $userRow = collect($userRow)->only($userHeaders)->all();
                /**
                 * Generate a new user ID and add the meta fields to the user row.
                 */
                $userRow['id'] = (string)Str::uuid();
                $userRow['password'] = Hash::make(Str::random(12));
                $userRow['created_at'] = Date::now();
                $userRow['updated_at'] = Date::now();

                /**
                 * Create the Address if present.
                 */
                if ($addressRow['address_line_1']) {
                    $addressRow['id'] = (string)Str::uuid();
                    $addressRow['created_at'] = Date::now();
                    $addressRow['updated_at'] = Date::now();
                    $addressRow['has_wheelchair_access'] = false;
                    $addressRow['has_induction_loop'] = false;
                    $addressRow['lat'] = 0;
                    $addressRow['lon'] = 0;

                    $userRow['location_id'] = $addressRow['id'];

                    $addressRowBatch[] = $addressRow;
                }

                /**
                 * Add the row to the batch array.
                 */
                $userRowBatch[] = $userRow;

                /**
                 * If the batch array has reach the import batch size create the insert queries.
                 */
                if (count($userRowBatch) === self::ROW_IMPORT_BATCH_SIZE) {
                    DB::table('locations')->insert($addressRowBatch);
                    $addressRowBatch = [];
                    DB::table('users')->insert($userRowBatch);
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                    $userRowBatch = [];
                }
            }

            /**
             * If there are a final batch that did not meet the import batch size, create queries for these.
             */
            if (count($userRowBatch) && count($userRowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table('locations')->insert($addressRowBatch);
                DB::table('users')->insert($userRowBatch);
                $importedRows += count($userRowBatch);
            }
        }, 5);

        return $importedRows;
    }
}
