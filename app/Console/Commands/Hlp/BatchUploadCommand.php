<?php

namespace App\Console\Commands\Hlp;

use App\BatchUpload\BatchUploader;
use App\Models\Taxonomy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BatchUploadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hlp:batch-upload {filename : The name of the spreadsheet to upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uploads an xlsx spreadsheet to the database';

    /**
     * Execute the console command.
     *
     * @param \App\BatchUpload\BatchUploader $batchUploader
     * @return mixed
     */
    public function handle(BatchUploader $batchUploader)
    {
        $this->line('Uploading file...');

        $path = storage_path('app/batch-upload/' . $this->argument('filename'));

        DB::transaction(function () use ($batchUploader, $path): void {
            $this->truncateCategoryTaxonomies();
            $this->uploadImport($batchUploader, $path);
        });

        $this->info('Spreadsheet uploaded');
    }

    protected function truncateCategoryTaxonomies(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('service_taxonomies')->delete();
        DB::table('collection_taxonomies')->delete();
        DB::table('taxonomies')
            ->where('id', '!=', Taxonomy::category()->id)
            ->where('id', '!=', Taxonomy::organisation()->id)
            ->where('parent_id', '!=', Taxonomy::organisation()->id)
            ->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * @param \App\BatchUpload\BatchUploader $batchUploader
     * @param string $path
     */
    protected function uploadImport(
        BatchUploader $batchUploader,
        string $path
    ): void {
        $batchUploader->upload($path);
    }
}
