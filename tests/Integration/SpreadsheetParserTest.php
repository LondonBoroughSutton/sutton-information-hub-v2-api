<?php

namespace Tests\Integration;

use App\BatchUpload\SpreadsheetParser;
use App\Models\Organisation;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SpreadsheetParserTest extends TestCase
{
    private $spreadsheet;

    private $xlsFilepath = 'test-spreadsheet.xls';
    private $xlsxFilepath = 'test-spreadsheet.xlsx';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $organisations = factory(Organisation::class, 20)->create();

        $headers = [
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $this->spreadsheet = self::createSpreadsheets($organisations, $headers);

        self::writeSpreadsheetsToDisk($this->spreadsheet, $this->xlsxFilepath, $this->xlsFilepath);
    }

    public static function createSpreadsheets(\Illuminate\Support\Collection $models, array $headers)
    {
        /** Create a new Spreadsheet Object **/
        $spreadsheet = new Spreadsheet();
        $columns = range('A', chr(count($headers) + 64));

        /**
         * Create the headers in row 1
         */
        foreach ($headers as $i => $header) {
            $spreadsheet->getActiveSheet()->setCellValue($columns[$i] . '1', $header);
        }

        /**
         * Populate all the other rows with data from the models
         */
        $row = 1;
        foreach ($models as $model) {
            $row++;
            foreach ($headers as $i => $header) {
                $spreadsheet->getActiveSheet()->setCellValue($columns[$i] . $row, $model->getAttribute($header));
            }
        }

        return $spreadsheet;
    }

    public static function writeSpreadsheetsToDisk(Spreadsheet $spreadsheet, String $xlsxFilepath, String $xlsFilepath)
    {
        $xlsxWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        $xlsWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xls");

        try {
            $xlsxWriter->save(Storage::disk('local')->path($xlsxFilepath));
            $xlsWriter->save(Storage::disk('local')->path($xlsFilepath));
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            dump($e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);
        Storage::disk('local')->delete([$this->xlsFilepath, $this->xlsxFilepath]);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_can_import_a_xls_spreadsheet()
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->Import(Storage::disk('local')->path($this->xlsFilepath));

        $spreadsheetParser->readHeaders();

        $this->assertEquals(['A' => 'name', 'B' => 'description', 'C' => 'url', 'D' => 'email', 'E' => 'phone'], $spreadsheetParser->headers);
    }

    /**
     * @test
     */
    public function it_can_import_a_xlsx_spreadsheet()
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->Import(Storage::disk('local')->path($this->xlsxFilepath));

        $spreadsheetParser->readHeaders();

        $this->assertEquals(['A' => 'name', 'B' => 'description', 'C' => 'url', 'D' => 'email', 'E' => 'phone'], $spreadsheetParser->headers);
    }

    /**
     * @test
     */
    public function it_can_read_rows_from_a_xls_spreadsheet()
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->Import(Storage::disk('local')->path($this->xlsFilepath));

        $organisations = Organisation::all();

        $spreadsheetParser->readHeaders();

        foreach ($spreadsheetParser->readRows() as $row) {
            $this->assertTrue($organisations->contains('name', $row['name']));
        }
    }
}

