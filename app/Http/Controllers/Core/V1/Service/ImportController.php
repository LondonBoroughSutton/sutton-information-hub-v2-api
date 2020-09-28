<?php

namespace App\Http\Controllers\Core\V1\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpreadsheetImportRequest;

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

    }
}
