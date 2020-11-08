<?php

namespace App\Console\Commands\Hlp;

use App\Models\LocalAuthority;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class LocalAuthoritiesImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hlp:la-import {json-url : The fully qualified URL of a .json file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Local Authorities from .json file';

    /**
     * The URL to request Json Local Authority data from.
     *
     * @var string
     */
    protected $jsonRequestUrl;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->jsonRequestUrl = $this->argument('json-url');
        $localAuthorityRecords = $this->fetchLocalAuthorityRecords();
        if ($localAuthorityRecords) {
            $this->createLocalAuthorityRecords($localAuthorityRecords);
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
            if ($this->output) {
                $this->error('Error Fetching Local Authority Records:');
                $this->error($e->getMessage());
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
        if ($this->output) {
            $this->info('Success: Imported Local Authority Records');
            $this->info(count($localAuthoritiesJson) . ' Local Authority records imported');
        }
    }
}
