<?php

namespace Tests\Integration;

use App\Console\Commands\Hlp\LocalAuthoritiesImportCommand;
use App\Models\LocalAuthority;
use Tests\TestCase;

class LocalAuthorityCommandTest extends TestCase
{
    /**
     * The URL of the Local Authoriy data .json file
     *
     * @var String
     **/
    protected $jsonImportUrl = 'http://geoportal1-ons.opendata.arcgis.com/datasets/fe6bcee87d95476abc84e194fe088abb_0.geojson';

    /**
     * Create an Object mocking the csv imported from the json import URL
     * See: https://data.gov.uk/dataset/24d87ad2-0fa9-4b35-816a-89f9d92b0042/local-authority-districts-april-2020-names-and-codes-in-the-united-kingdom
     *
     * @return Object
     **/
    public function jsonImportMock()
    {
        $json = <<<EOT
{
    "type": "FeatureCollection",
    "name": "LAD_DEC_2020_UK_NC",
    "features": [
        { "type": "Feature", "properties": { "FID": 133, "LAD20CD": "S12000042", "LAD20NM": "Dundee City", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 134, "LAD20CD": "S12000045", "LAD20NM": "East Dunbartonshire", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 135, "LAD20CD": "E06000060", "LAD20NM": "Buckinghamshire", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 136, "LAD20CD": "S12000047", "LAD20NM": "Fife", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 137, "LAD20CD": "E07000008", "LAD20NM": "Cambridge", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 138, "LAD20CD": "S12000048", "LAD20NM": "Perth and Kinross", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 139, "LAD20CD": "E07000009", "LAD20NM": "East Cambridgeshire", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 140, "LAD20CD": "S12000049", "LAD20NM": "Glasgow City", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 141, "LAD20CD": "E07000010", "LAD20NM": "Fenland", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 142, "LAD20CD": "S12000050", "LAD20NM": "North Lanarkshire", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 143, "LAD20CD": "E07000011", "LAD20NM": "Huntingdonshire", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 144, "LAD20CD": "W06000001", "LAD20NM": "Isle of Anglesey", "LAD20NMW": "Ynys Môn" }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 145, "LAD20CD": "E07000012", "LAD20NM": "South Cambridgeshire", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 146, "LAD20CD": "W06000002", "LAD20NM": "Gwynedd", "LAD20NMW": "Gwynedd" }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 147, "LAD20CD": "E07000026", "LAD20NM": "Allerdale", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 148, "LAD20CD": "W06000003", "LAD20NM": "Conwy", "LAD20NMW": "Conwy" }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 371, "LAD20CD": "N09000008", "LAD20NM": "Mid and East Antrim", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 372, "LAD20CD": "N09000009", "LAD20NM": "Mid Ulster", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 373, "LAD20CD": "N09000010", "LAD20NM": "Newry, Mourne and Down", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 374, "LAD20CD": "N09000011", "LAD20NM": "Ards and North Down", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 375, "LAD20CD": "S12000005", "LAD20NM": "Clackmannanshire", "LAD20NMW": null }, "geometry": null },
        { "type": "Feature", "properties": { "FID": 376, "LAD20CD": "S12000006", "LAD20NM": "Dumfries and Galloway", "LAD20NMW": null }, "geometry": null }
    ]
}
EOT;

        return json_decode($json);
    }
    /**
     * @test
     */
    public function it_can_create_local_authorities_from_imported_data()
    {
        $json = $this->jsonImportMock();
        (new LocalAuthoritiesImportCommand())->createLocalAuthorityRecords($json->features);

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Cambridge',
            'alt_name' => null,
            'code' => 'E07000008',
        ]);

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Isle of Anglesey',
            'alt_name' => 'Ynys Môn',
            'code' => 'W06000001',
        ]);

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Mid and East Antrim',
            'alt_name' => null,
            'code' => 'N09000008',
        ]);

        $this->assertDatabaseHas(table(LocalAuthority::class), [
            'name' => 'Dumfries and Galloway',
            'alt_name' => null,
            'code' => 'S12000006',
        ]);
    }

    /**
     * @test
     */
    public function it_displays_an_error_if_it_cannot_fetch_the_remote_file()
    {
        $this->artisan('hlp:la-import', ['json-url' => 'http://example.org/this/file/does/not/exist.json'])
            ->expectsOutput('Error Fetching Local Authority Records:')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_displays_a_success_message_on_import()
    {
        $this->artisan('hlp:la-import', ['json-url' => $this->jsonImportUrl])
            ->expectsOutput('Success: Imported Local Authority Records')
            ->assertExitCode(0);
    }
}
