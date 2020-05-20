<?php

namespace App\BatchUpload;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BatchUploader
{
    const EMAIL_PLACEHOLDER = 'placeholder@example.com';

    /**
     * @var \PhpOffice\PhpSpreadsheet\Reader\Xlsx
     */
    protected $reader;

    /**
     * BatchUploader constructor.
     */
    public function __construct()
    {
        $this->reader = new XlsxReader();
        $this->reader->setReadDataOnly(true);
    }

    /**
     * Validates and then uploads the file.
     *
     * @param string $filePath
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \Exception
     */
    public function upload(string $filePath)
    {
        // Load the spreadsheet.
        $spreadsheet = $this->reader->load($filePath);

        // Load each worksheet.
        $organisationsSheet = $spreadsheet->getSheetByName('Organisations');
        $servicesSheet = $spreadsheet->getSheetByName('Services');
        $topicsSheet = $spreadsheet->getSheetByName('Topics');

        // Convert the worksheets to associative arrays.
        $organisations = $this->toArray($organisationsSheet);
        $services = $this->toArray($servicesSheet);
        $categoryTaxonomies = $this->toArray($topicsSheet);

        // Transform and insert into the database.
        $this->processCategoryTaxonomies($categoryTaxonomies);
        $this->processOrganisations($organisations);
        $this->processServices($services);
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    protected function toArray(Worksheet $sheet): array
    {
        $array = $sheet->toArray();
        $headings = array_shift($array);

        $array = array_map(function ($row) use ($headings) {
            $resource = [];

            foreach ($headings as $column => $heading) {
                $resource[$heading] = $row[$column];
            }

            return $resource;
        }, $array);

        return $array;
    }

    /**
     * @param array $organisations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function processOrganisations(array $organisations): EloquentCollection
    {
        return (new EloquentCollection($organisations))
            ->map(function (array $organisation): Organisation {
                $email = $organisation['email'];
                if ($organisation['email'] === null && $organisation['phone'] === null) {
                    $email = static::EMAIL_PLACEHOLDER;
                }

                return Organisation::create([
                    'id' => $organisation['id'],
                    'slug' => $organisation['slug'],
                    'name' => $organisation['name'],
                    'description' => $organisation['description'],
                    'url' => $organisation['url'],
                    'email' => $email,
                    'phone' => $organisation['phone'],
                ]);
            });
    }

    /**
     * @param array $services
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function processServices(array $services): EloquentCollection
    {
        return (new EloquentCollection($services))
            ->map(function (array $service): Service {
                $serviceModel = Service::create([
                    'organisation_id' => $service['organisation_id'],
                    'slug' => $service['slug'],
                    'name' => $service['name'],
                    'status' => Service::STATUS_ACTIVE,
                    'intro' => null,
                    'description' => $service['description'],
                    'wait_time' => null,
                    'is_free' => $service['is_free'] === 'Yes',
                    'fees_text' => null,
                    'fees_url' => null,
                    'testimonial' => null,
                    'video_embed' => null,
                    'url' => $service['url'],
                    'contact_name' => $service['contact_name'],
                    'contact_phone' => $service['contact_phone'],
                    'contact_email' => $service['contact_email'],
                    'show_referral_disclaimer' => false,
                    'referral_method' => Service::REFERRAL_METHOD_NONE,
                    'referral_button_text' => null,
                    'referral_email' => null,
                    'referral_url' => null,
                    'last_modified_at' => Date::now(),
                ]);

                // Create the service criterion record.
                $serviceModel->serviceCriterion()->create([
                    'age_group' => null,
                    'disability' => null,
                    'employment' => null,
                    'gender' => null,
                    'housing' => null,
                    'income' => null,
                    'language' => null,
                    'other' => null,
                ]);

                foreach (range(1, 7) as $topicNumber) {
                    if ($service["topic_id_{$topicNumber}"] !== null) {
                        $serviceModel->serviceTaxonomies()->create([
                            'taxonomy_id' => Taxonomy::query()
                                ->where('name', '=', $service["topic_id_{$topicNumber}"])
                                ->firstOrFail()
                                ->id,
                        ]);
                    }
                }

                return $serviceModel;
            });
    }

    /**
     * @param array $taxonomies
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function processCategoryTaxonomies(array $taxonomies): EloquentCollection
    {
        $categoryTaxonomyId = Taxonomy::category()->id;

        $taxonomies = (new EloquentCollection($taxonomies))
            ->map(function (array $taxonomy) use ($categoryTaxonomyId): Taxonomy {
                // DB used to prevent model observer events.
                DB::table('taxonomies')->insert([
                    'id' => $taxonomy['id'],
                    'parent_id' => $taxonomy['parent_id'] ?: $categoryTaxonomyId,
                    'name' => $taxonomy['name'],
                    'order' => 0, // Placeholder until below.
                    'created_at' => Date::now(),
                    'updated_at' => Date::now(),
                ]);

                return Taxonomy::find($taxonomy['id']);
            })
            ->each(function (Taxonomy $taxonomy): void {
                $highestSiblingOrder = Taxonomy::query()
                    ->where('parent_id', '=', $taxonomy->parent_id)
                    ->max('order') ?? 0;

                // DB used to prevent model observer events.
                DB::table('taxonomies')
                    ->where('id', '=', $taxonomy->id)
                    ->update(['order' => $highestSiblingOrder + 1]);
            });

        return $taxonomies;
    }
}
