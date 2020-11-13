<?php

namespace App\Http\Controllers\Core\V1\User;

use App\BatchUpload\SpreadsheetParser;
use App\BatchUpload\StoresSpreadsheets;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ImportRequest;
use App\Jobs\NotifyNewUser;
use App\Models\Role;
use App\Models\UserRole;
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
     * Local Authority ID to assign to Users.
     *
     * @var string
     */
    protected $localAuthorityId = null;

    /**
     * Roles to assign to Users.
     *
     * @var array
     */
    protected $roles = [];

    /**
     * Role Ids.
     *
     * @var array
     */
    protected $roleIds = [];

    /**
     * OrganisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->roleIds = [
            'globalAdmin' => Role::globalAdmin()->id,
            'localAdmin' => Role::localAdmin()->id,
            'organisationAdmin' => Role::organisationAdmin()->id,
            'serviceAdmin' => Role::serviceAdmin()->id,
            'serviceWorker' => Role::serviceWorker()->id,
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Organisation\ImportRequest $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ImportRequest $request)
    {
        $this->localAuthorityId = $request->input('local_authority_id', null);
        $this->roles = $this->trimRoles($this->uniqueRoles($request->getUserRoles()));
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
            $userRowBatch = $addressRowBatch = $roleRowBatch = [];
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
                 * Assign the Local Authority ID if set.
                 */
                $userRow['local_authority_id'] = $this->localAuthorityId;

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
                 * Assign Roles if supplied.
                 */
                if (count($this->roles)) {
                    $roleRows = $this->createRoles($userRow['id']);
                    $roleRowBatch = array_merge($roleRowBatch, $roleRows);
                }

                /**
                 * Add the NotifyNewUser job to the queue with a delay.
                 */
                NotifyNewUser::dispatch($userRow['id'])
                    ->delay(now()->addMinutes(10));

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
                    $userRowBatch = [];
                    DB::table('user_roles')->insert($roleRowBatch);
                    $roleRowBatch = [];
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                }
            }

            /**
             * If there are a final batch that did not meet the import batch size, create queries for these.
             */
            if (count($userRowBatch) && count($userRowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table('locations')->insert($addressRowBatch);
                DB::table('users')->insert($userRowBatch);
                DB::table('user_roles')->insert($roleRowBatch);
                $importedRows += count($userRowBatch);
            }
        }, 5);

        return $importedRows;
    }

    /**
     * @param \App\Models\UserRole[] $userRoles
     * @return \App\Models\UserRole[]
     */
    protected function uniqueRoles(array $userRoles): array
    {
        return collect($userRoles)
            ->unique(function (UserRole $userRole): array {
                return [
                    'role_id' => $userRole['role_id'],
                    'service_id' => $userRole['service_id'] ?? null,
                    'organisation_id' => $userRole['organisation_id'] ?? null,
                ];
            })
            ->all();
    }

    /**
     * Trim roles with overlapping privileges.
     *
     * @param array $userRoles
     * @return array
     */
    public function trimRoles(array $userRoles)
    {
        $organisationIds = [];
        $serviceIds = [];
        $serviceAdminServiceIds = [];
        $organisationServiceIds = [];

        foreach ($userRoles as $role) {
            // If Global Admin is set other roles are unnecessary
            if ($role['role_id'] === $this->roleIds['globalAdmin']) {
                return [$role];
            }
            if ($role['organisation_id']) {
                $organisationIds[] = $role['organisation_id'];
            }
            if ($role['service_id']) {
                $serviceIds[] = $role['service_id'];
                if ($role['role_id'] === $this->roleIds['serviceAdmin']) {
                    $serviceAdminServiceIds[] = $role['service_id'];
                }
            }
        }
        /**
         * Filter out services which belong to organisations.
         */
        if (count($organisationIds) && count($serviceIds)) {
            $organisationServiceIds = DB::table('services')->whereIn('organisation_id', $organisationIds)->pluck('id')->all();
            $serviceIds = array_filter($serviceIds, function ($serviceId) use ($organisationServiceIds) {
                return !in_array($serviceId, $organisationServiceIds);
            });
        }

        /**
         * Filter the roles to the minimum required.
         */
        return array_filter($userRoles, function ($userRole) use ($serviceIds, $serviceAdminServiceIds) {
            /**
             * Include the role if it is an Organisation admin or a Local Admin.
             */
            if ($userRole['role_id'] === $this->roleIds['localAdmin'] || $userRole['role_id'] === $this->roleIds['organisationAdmin']) {
                return true;
            }
            /**
             * Must be a Service Admin or Service Worker, so reject it if not in the filtered service IDs.
             */
            if (!in_array($userRole['service_id'], $serviceIds)) {
                return false;
            }
            /**
             * Reject it if a Service Worker already covered by a Service Admin.
             */
            if ($userRole['role_id'] === $this->roleIds['serviceWorker'] && in_array($userRole['service_id'], $serviceAdminServiceIds)) {
                return false;
            }

            return true;
        });
    }

    /**
     * Create the DB insert for the user_roles table.
     *
     * @param string $userId
     * @return array
     */
    public function createRoles(string $userId)
    {
        $userRoles = [];
        foreach ($this->roles as $role) {
            $userRoles[] = [
                'id' => uuid(),
                'user_id' => $userId,
                'role_id' => $role->role_id,
                'organisation_id' => $role->organisation_id ?? null,
                'service_id' => $role->service_id ?? null,
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ];
        }

        return $userRoles;
    }
}
