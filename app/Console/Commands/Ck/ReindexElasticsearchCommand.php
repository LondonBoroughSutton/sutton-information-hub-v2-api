<?php

namespace App\Console\Commands\Ck;

use App\Models\IndexConfigurators\EventsIndexConfigurator;
use App\Models\IndexConfigurators\PagesIndexConfigurator;
use App\Models\IndexConfigurators\ServicesIndexConfigurator;
use App\Models\OrganisationEvent;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReindexElasticsearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ck:reindex-elasticsearch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes the indices if they exist, recreates them, and then imports all data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Config::get('scout.driver') !== 'elastic') {
            $this->warn('Did not reindex due to not using the [elastic] Scout driver.');

            return;
        }

        $this->line('Drop all elastic search indices and re-run migrations');
        $this->call('elastic:migrate:fresh');

        if (Schema::hasTable((new Service())->getTable())) {
            $this->import(Service::class);
        }

        if (Schema::hasTable((new OrganisationEvent())->getTable())) {
            $this->import(OrganisationEvent::class);
        }

        if (Schema::hasTable((new Page())->getTable())) {
            $this->import(Page::class);
        }
    }

    protected function import(string $model): void
    {
        $this->line("Importing documents for [{$model}]...");
        $this->call('ck:scout-import', ['model' => $model]);
    }
}
