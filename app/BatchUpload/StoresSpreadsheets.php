<?php

namespace App\BatchUpload;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Mime\MimeTypes;

/**
 * Accept and store Base 64 encoded Spreadsheet data
 */
trait StoresSpreadsheets
{

    /**
     * Import a base64 encode spreadsheet
     *
     * @param String $spreadsheet
     * @return Array
     **/
    public function processSpreadsheet(String $spreadsheet)
    {
        $filePath = $this->storeBase64FileString($spreadsheet, 'batch-upload');

        if (!Storage::disk('local')->exists($filePath) || !is_readable(Storage::disk('local')->path($filePath))) {
            throw new FileNotFoundException($filePath);
        }

        $rows = [
            'rejected' => $this->validateSpreadsheet($filePath),
            'imported' => 0,
        ];

        if (!count($rows['rejected'])) {
            try {
                $rows['imported'] = $this->importSpreadsheet($filePath);
            } catch (\Exception $e) {
                Storage::disk('local')->delete($filePath);

                abort(500, $e->getMessage());
            }
        }

        Storage::disk('local')->delete($filePath);

        return $rows;
    }
    /**
     * Store a Base 64 encoded data string.
     *
     * @param String $file_data
     * @param String $path
     * @throws Illuminate\Validation\ValidationException
     * @return String
     */
    protected function storeBase64FileString(String $file_data, String $path)
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
     * Store a binary file blob and update the models properties.
     *
     * @param String $blob
     * @param String $path
     * @param String $mime_type
     * @param String $ext
     * @return String
     */
    protected function storeBinaryUpload(String $blob, String $path, $mime_type = null, $ext = null)
    {
        $path = empty($path) ? '' : trim($path, '/') . '/';
        $mime_type = $mime_type ?? $this->getFileStringMimeType($blob);
        $ext = $ext ?? $this->guessFileExtension($mime_type);
        $filename = md5($blob) . '.' . $ext;
        Storage::disk('local')->put($path . $filename, $blob);

        return $path . $filename;
    }

    /**
     * Get the mime type of a binary file string.
     *
     * @var String $file_str
     * @return String
     */
    protected function getFileStringMimeType(String $file_str)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $file_str);
        finfo_close($finfo);

        return $mime_type;
    }

    /**
     * Guess the extension for a file from it's mime-type.
     *
     * @param String $mime_type
     * @return String
     */
    protected function guessFileExtension(String $mime_type)
    {
        return (new MimeTypes())->getExtensions($mime_type)[0] ?? null;
    }
}
