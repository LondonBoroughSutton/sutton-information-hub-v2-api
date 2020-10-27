<?php

use App\Models\LocalAuthority;
use function GuzzleHttp\json_decode;
use GuzzleHttp\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class LocalAuthoritySeeder extends Seeder
{

    /**
     * The URL to request Json Local Authority data from
     *
     * @var String
     **/
    protected $jsonRequestUrl;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->jsonRequestUrl = env('LOCAL_AUTHORITY_DATA_URL');
        if ($localAuthorityRecords = $this->fetchLocalAuthorityRecords()) {
            $this->createLocalAuthorityRecords($localAuthorityRecords);
        }
    }

    /**
     * Create the Local Authority records from imported data
     *
     * @param Array $localAuthoritiesJson
     * @return Boolean
     **/
    public function fetchLocalAuthorityRecords()
    {
        $client = new Client();
        try {
            $response = $client->get($this->jsonRequestUrl);
            $jsonResponse = json_decode($response);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            if ($this->command) {
                $this->command->getOutput()->writeln("<error>Fetch Local Authority Records:</error> {$e->getMessage()}");
            }
        }

        return $jsonResponse->features ?? [];
    }

    /**
     * Create the Local Authority records from imported data
     *
     * @param Array $localAuthoritiesJson
     * @return Boolean
     **/
    public function createLocalAuthorityRecords(array $localAuthoritiesJson)
    {
        \DB::transaction(function () use ($localAuthoritiesJson) {
            foreach ($localAuthoritiesJson as $localAuthority) {
                \DB::table(table(LocalAuthority::class))->updateOrInsert(
                    ['code' => $localAuthority->properties->LAD20CD],
                    [
                        'id' => uuid(),
                        'name' => $localAuthority->properties->LAD20NM,
                        'alt_name' => $localAuthority->properties->LAD20NMW,
                        'created_at' => Date::now(),
                        'updated_at' => Date::now(),
                    ]
                );
            }
        });
        if ($this->command) {
            $this->command->getOutput()->writeln("<info>Imported Local Authority Records:</info> {count($locaAuthorityJson)}");
        }
    }
}
