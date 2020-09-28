<?php

namespace App\Contracts;

interface SpreadsheetController
{
    /**
     * Validate the spreadsheet rows.
     *
     * @param String $filePath
     * @return Array
     */
    public function validateSpreadsheet(string $filePath);

    /**
     * Import the uploaded file contents.
     *
     * @param String $filePath
     */
    public function importSpreadsheet(String $filePath);
}
