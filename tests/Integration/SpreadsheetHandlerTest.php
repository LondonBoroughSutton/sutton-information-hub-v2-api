<?php

namespace Tests\Integration;

use App\BatchUpload\SpreadsheetHandler;
use App\Models\Organisation;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpreadsheetHandlerTest extends TestCase
{
    private $spreadsheet;

    private $xlsFilepath = 'batch-upload/test-spreadsheet.xls';
    private $xlsxFilepath = 'batch-upload/test-spreadsheet.xlsx';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSpreadsheets();
    }

    private function createSpreadsheets($maxRows = 20)
    {
        /** Create a new Spreadsheet Object **/
        $this->spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $organisations = factory(Organisation::class, $maxRows)->create();

        $this->spreadsheet->getActiveSheet()->setCellValue('A1', 'name');
        $this->spreadsheet->getActiveSheet()->setCellValue('B1', 'description');
        $this->spreadsheet->getActiveSheet()->setCellValue('C1', 'url');
        $this->spreadsheet->getActiveSheet()->setCellValue('D1', 'email');
        $this->spreadsheet->getActiveSheet()->setCellValue('E1', 'phone');

        $row = 1;
        foreach ($organisations as $organisation) {
            $row++;
            $this->spreadsheet->getActiveSheet()->setCellValue('A' . $row, $organisation->name);
            $this->spreadsheet->getActiveSheet()->setCellValue('B' . $row, $organisation->description);
            $this->spreadsheet->getActiveSheet()->setCellValue('C' . $row, rand(0, 1) ? $organisation->url : '');
            $this->spreadsheet->getActiveSheet()->setCellValue('D' . $row, rand(0, 1) ? $organisation->email : '');
            $this->spreadsheet->getActiveSheet()->setCellValue('E' . $row, rand(0, 1) ? $organisation->phone : '');
        }

        $xlsxWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->spreadsheet, "Xlsx");
        $xlsWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->spreadsheet, "Xls");

        try {
            $xlsxWriter->save(Storage::disk('local')->path($this->xlsxFilepath));
            $xlsWriter->save(Storage::disk('local')->path($this->xlsFilepath));
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
        $spreadsheetHandler = new SpreadsheetHandler();

        $spreadsheetHandler->Import(Storage::disk('local')->path($this->xlsFilepath));

        $spreadsheetHandler->readHeaders();

        $this->assertEquals(['A' => 'name', 'B' => 'description', 'C' => 'url', 'D' => 'email', 'E' => 'phone'], $spreadsheetHandler->headers);
    }

    /**
     * @test
     */
    public function it_can_import_a_xlsx_spreadsheet()
    {
        $spreadsheetHandler = new SpreadsheetHandler();

        $spreadsheetHandler->Import(Storage::disk('local')->path($this->xlsxFilepath));

        $spreadsheetHandler->readHeaders();

        $this->assertEquals(['A' => 'name', 'B' => 'description', 'C' => 'url', 'D' => 'email', 'E' => 'phone'], $spreadsheetHandler->headers);
    }

    /**
     * @test
     */
    public function it_can_read_rows_from_a_xls_spreadsheet()
    {
        $spreadsheetHandler = new SpreadsheetHandler();

        $spreadsheetHandler->Import(Storage::disk('local')->path($this->xlsFilepath));

        $organisations = Organisation::all();

        $spreadsheetHandler->readHeaders();

        foreach ($spreadsheetHandler->readRows() as $row) {
            $this->assertTrue($organisations->contains('name', $row['name']));
        }
    }
}
