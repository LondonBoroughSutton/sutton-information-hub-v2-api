<?php

namespace App\Contracts;

interface SpreadsheetController
{
    /**
     * Validate the spreadsheet rows.
     *
     * @param string $filePath
     * @return array
     */
    public function validateSpreadsheet(string $filePath);

    /**
     * Check for existing matching entities
     *
     * @return array
     **/
    public function rowsExist();

    /**
     * Import the uploaded file contents.
     *
     * @param string $filePath
     */
    public function importSpreadsheet(string $filePath);
}
