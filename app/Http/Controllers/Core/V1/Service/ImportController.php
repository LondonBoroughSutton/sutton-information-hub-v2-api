<?php

namespace App\Http\Controllers\Core\V1\Service;

use App\BatchUpload\SpreadsheetParser;
use App\Contracts\SpreadsheetController;
use App\Http\Controllers\Controller;
use App\Http\Requests\SpreadsheetImportRequest;
use App\Models\Role;
use App\Models\Service;
use App\Models\UserRole;
use App\Rules\IsOrganisationAdmin;
use App\Rules\MarkdownMaxLength;
use App\Rules\MarkdownMinLength;
use App\Rules\UserHasRole;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller implements SpreadsheetController
{
    /**
     * Number of rows to import at once.
     */
    const ROW_IMPORT_BATCH_SIZE = 100;

    /**
     * Organisation ID to which Services will be assigned
     *
     * @var String
     **/
    protected $organisationId = null;

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
        $this->organisationId = $request->input('organisation_id');

        if (!(new IsOrganisationAdmin($this->user()))->passes('id', $this->organisationId)) {
            throw ValidationException::withMessages([
                'organisation_id' => 'The organisation_id field must contain an ID for an organisation you are an organisation admin for',
            ]);
        }
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
                'type' => [
                    'required',
                    Rule::in([
                        Service::TYPE_SERVICE,
                        Service::TYPE_ACTIVITY,
                        Service::TYPE_CLUB,
                        Service::TYPE_GROUP,
                        Service::TYPE_HELPLINE,
                        Service::TYPE_INFORMATION,
                        Service::TYPE_APP,
                    ]),
                ],
                'status' => [
                    'required',
                    Rule::in([
                        Service::STATUS_ACTIVE,
                        Service::STATUS_INACTIVE,
                    ]),
                    new UserHasRole(
                        $this->user('api'),
                        new UserRole([
                            'user_id' => $this->user('api')->id,
                            'role_id' => Role::globalAdmin()->id,
                        ]),
                        Service::STATUS_INACTIVE
                    ),
                ],
                'intro' => ['required', 'string', 'min:1', 'max:300'],
                'description' => ['required', 'string', new MarkdownMinLength(1), new MarkdownMaxLength(1600)],
                'is_free' => ['required', 'boolean'],
                'url' => ['required', 'url', 'max:255'],
                'show_referral_disclaimer' => [
                    'required',
                    'boolean',
                    new UserHasRole(
                        $this->user('api'),
                        new UserRole([
                            'user_id' => $this->user('api')->id,
                            'role_id' => Role::superAdmin()->id,
                        ]),
                        ($this->referral_method === Service::REFERRAL_METHOD_NONE) ? false : true
                    ),
                ],
                'referral_method' => [
                    'required',
                    Rule::in([
                        Service::REFERRAL_METHOD_INTERNAL,
                        Service::REFERRAL_METHOD_EXTERNAL,
                        Service::REFERRAL_METHOD_NONE,
                    ]),
                    new UserHasRole(
                        $this->user('api'),
                        new UserRole([
                            'user_id' => $this->user('api')->id,
                            'role_id' => Role::globalAdmin()->id,
                        ]),
                        Service::REFERRAL_METHOD_NONE
                    ),
                ],
                'referral_email' => [
                    'required_if:referral_method,' . Service::REFERRAL_METHOD_INTERNAL,
                    'present',
                    'nullable',
                    'email',
                    'max:255',
                    new UserHasRole(
                        $this->user('api'),
                        new UserRole([
                            'user_id' => $this->user('api')->id,
                            'role_id' => Role::globalAdmin()->id,
                        ]),
                        null
                    ),
                ],
                'referral_url' => [
                    'required_if:referral_method,' . Service::REFERRAL_METHOD_EXTERNAL,
                    'present',
                    'nullable',
                    'url',
                    'max:255',
                    new UserHasRole(
                        $this->user('api'),
                        new UserRole([
                            'user_id' => $this->user('api')->id,
                            'role_id' => Role::globalAdmin()->id,
                        ]),
                        null
                    ),
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
            $serviceAdminRoleId = Role::serviceAdmin()->id;
            $globalAdminIds = Role::globalAdmin()->users()->pluck('users.id');
            $serviceRowBatch = $adminRowBatch = [];
            foreach ($spreadsheetParser->readRows() as $serviceRow) {
                $serviceRow['id'] = (string) Str::uuid();
                $serviceRow['slug'] = Str::slug($serviceRow['name'] . ' ' . uniqid(), '-');
                $serviceRow['organisation_id'] = $this->organisationId;
                $serviceRow['created_at'] = Date::now();
                $serviceRow['updated_at'] = Date::now();
                $serviceRowBatch[] = $serviceRow;

                foreach ($globalAdminIds as $globalAdminId) {
                    $adminRowBatch[] = [
                        'id' => (string) Str::uuid(),
                        'user_id' => $globalAdminId,
                        'role_id' => $serviceAdminRoleId,
                        'service_id' => $serviceRow['id'],
                        'created_at' => Date::now(),
                        'updated_at' => Date::now(),
                    ];
                }

                if (count($serviceRowBatch) === self::ROW_IMPORT_BATCH_SIZE) {
                    DB::table('services')->insert($serviceRowBatch);
                    DB::table('user_roles')->insert($adminRowBatch);
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                    $serviceRowBatch = $adminRowBatch = [];
                }
            }

            if (count($serviceRowBatch) && count($serviceRowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table('services')->insert($serviceRowBatch);
                DB::table('user_roles')->insert($adminRowBatch);
                $importedRows += count($serviceRowBatch);
            }
        }, 5);

        return $importedRows;
    }
}
