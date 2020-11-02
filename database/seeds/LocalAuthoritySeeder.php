<?php

use App\Models\LocalAuthority;
use GuzzleHttp\Client;
use function GuzzleHttp\json_decode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class LocalAuthoritySeeder extends Seeder
{
    /**
     * The URL to request Json Local Authority data from.
     *
     * @var string
     */
    protected $jsonRequestUrl;

    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->jsonRequestUrl = config('hlp.local_authority_data_url');
        if ($this->jsonRequestUrl) {
            $localAuthorityRecords = $this->fetchLocalAuthorityRecords();
            if ($localAuthorityRecords) {
                $this->createLocalAuthorityRecords($localAuthorityRecords);
            }
        }
    }

    /**
     * Create the Local Authority records from imported data.
     *
     * @param array $localAuthoritiesJson
     * @return array || Null
     */
    public function fetchLocalAuthorityRecords()
    {
        $client = new Client();
        try {
            $response = $client->get($this->jsonRequestUrl);
            if (200 === $response->getStatusCode() && $response->getBody()->isReadable()) {
                $jsonResponse = json_decode($response->getBody()->getContents());
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            if ($this->command) {
                $this->command->error('Error Fetching Local Authority Records:');
                $this->command->error($e->getMessage());
            }
        }

        return $jsonResponse->features ?? [];
    }

    /**
     * Create the Local Authority records from imported data.
     *
     * @param array $localAuthoritiesJson
     * @return bool
     */
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
            $this->command->info('Imported ' . count($localAuthoritiesJson) . ' Local Authority Records');
        }
    }
}
