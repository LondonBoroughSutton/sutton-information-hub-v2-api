<?php

namespace Tests\Integration;

use Tests\TestCase;

class LocalAuthoritySeederTest extends TestCase
{

    /**
     * Create an array mocking the csv imported from:
     * https://data.gov.uk/dataset/24d87ad2-0fa9-4b35-816a-89f9d92b0042/local-authority-districts-april-2020-names-and-codes-in-the-united-kingdom
     *
     * @return Array
     **/
    public function csvImportMock()
    {
        return [
            ['FID', 'LAD20CD', 'LAD20NM', 'LAD20NMW'],
            ['105', 'E08000025', 'Birmingham'],
            ['106', 'E08000026', 'Coventry'],
            ['107', 'E08000027', 'Dudley'],
            ['108', 'E08000028', 'Sandwell'],
            ['109', 'E08000029', 'Solihull'],
            ['110', 'E08000030', 'Walsall'],
            ['111', 'E06000059', 'Dorset'],
            ['112', 'E08000031', 'Wolverhampton'],
            ['113', 'S12000014', 'Falkirk'],
            ['114', 'S12000017', 'Highland'],
            ['115', 'S12000018', 'Inverclyde'],
            ['116', 'S12000019', 'Midlothian'],
            ['144', 'W06000001', 'Isle of Anglesey', 'Ynys Môn'],
            ['145', 'E07000012', 'South Cambridgeshire'],
            ['146', 'W06000002', 'Gwynedd', 'Gwynedd'],
            ['147', 'E07000026', 'Allerdale	'],
            ['148', 'W06000003', 'Conwy', 'Conwy'],
            ['149', 'E07000027', 'Barrow-in-Furness	'],
            ['150', 'W06000004', 'Denbighshire', 'Sir Ddinbych'],
            ['151', 'E07000028', 'Carlisle	'],
            ['152', 'W06000005', 'Flintshire', 'Sir y Fflint'],
            ['360', 'E09000030', 'Tower Hamlets'],
            ['361', 'E09000031', 'Waltham Forest'],
            ['362', 'E09000032', 'Wandsworth'],
            ['363', 'E09000033', 'Westminster'],
            ['364', 'N09000001', 'Antrim and Newtownabbey'],
            ['365', 'N09000002', 'Armagh City, Banbridge and Craigavon'],
            ['366', 'N09000003', 'Belfast'],
            ['367', 'N09000004', 'Causeway Coast and Glens'],
            ['368', 'N09000005', 'Derry City and Strabane'],
            ['369', 'N09000006', 'Fermanagh and Omagh'],
            ['370', 'N09000007', 'Lisburn and Castlereagh'],
        ];
    }
    /**
     * @test
     */
    public function it_can_create_local_authorities_from_imported_data()
    {
        (new LocalAuthoritySeeder())->createLocalAuthorityRecords($this->csvImportMock());

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Carlisle',
            'alt_name' => null,
            'code' => 'E07000028',
        ]);

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Isle of Anglesey',
            'alt_name' => 'Ynys Môn',
            'code' => 'W06000001',
        ]);

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Causeway Coast and Glens',
            'alt_name' => null,
            'code' => 'N09000004',
        ]);

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Midlothian',
            'alt_name' => null,
            'code' => 'S12000019',
        ]);

        $this->assertDatabaseMissing(table(LocalAuthority::class), [
            'name' => 'LAD20NM',
            'alt_name' => 'LAD20NMW',
            'code' => 'LAD20CD',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_a_file_not_found_exception_on_error_getting_csv()
    {
        putenv('LOCAL_AUTHORITY_DATA_URL=http://example.org/this/file/does/not/exist.json');
        $this->expectException(\Illuminate\Filesystem\FileNotFoundException::class);
        $this->seed(LocalAuthoritySeeder::class);
    }
}
