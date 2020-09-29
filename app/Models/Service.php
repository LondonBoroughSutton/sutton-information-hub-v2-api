<?php

namespace App\Models;

use App\Emails\Email;
use App\Models\IndexConfigurators\ServicesIndexConfigurator;
use App\Models\Mutators\ServiceMutators;
use App\Models\Relationships\ServiceRelationships;
use App\Models\Scopes\ServiceScopes;
use App\Notifications\Notifiable;
use App\Notifications\Notifications;
use App\Sms\Sms;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use ScoutElastic\Searchable;

class Service extends Model implements Notifiable
{
    use DispatchesJobs;
    use Notifications;
    use Searchable;
    use ServiceMutators;
    use ServiceRelationships;
    use ServiceScopes;

    const TYPE_SERVICE = 'service';
    const TYPE_ACTIVITY = 'activity';
    const TYPE_CLUB = 'club';
    const TYPE_GROUP = 'group';
    const TYPE_HELPLINE = 'helpline';
    const TYPE_INFORMATION = 'information';
    const TYPE_APP = 'app';
    const TYPE_ADVICE = 'advice';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const WAIT_TIME_ONE_WEEK = 'one_week';
    const WAIT_TIME_TWO_WEEKS = 'two_weeks';
    const WAIT_TIME_THREE_WEEKS = 'three_weeks';
    const WAIT_TIME_MONTH = 'month';
    const WAIT_TIME_LONGER = 'longer';

    const REFERRAL_METHOD_INTERNAL = 'internal';
    const REFERRAL_METHOD_EXTERNAL = 'external';
    const REFERRAL_METHOD_NONE = 'none';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_national' => 'boolean',
        'is_free' => 'boolean',
        'show_referral_disclaimer' => 'boolean',
        'last_modified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The Elasticsearch index configuration class.
     *
     * @var string
     */
    protected $indexConfigurator = ServicesIndexConfigurator::class;

    /**
     * Allows you to set different search algorithms.
     *
     * @var array
     */
    protected $searchRules = [
        //
    ];

    /**
     * The mapping for the fields.
     *
     * @var array
     */
    protected $mapping = [
        'properties' => [
            'id' => ['type' => 'keyword'],
            'name' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                ],
            ],
            'type' => ['type' => 'keyword'],
            'intro' => ['type' => 'text'],
            'description' => ['type' => 'text'],
            'wait_time' => ['type' => 'keyword'],
            'is_free' => ['type' => 'boolean'],
            'is_national' => ['type' => 'boolean'],
            'status' => ['type' => 'keyword'],
            'organisation_name' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                ],
            ],
            'taxonomy_categories' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                ],
            ],
            'collection_categories' => ['type' => 'keyword'],
            'collection_personas' => ['type' => 'keyword'],
            'service_locations' => [
                'type' => 'nested',
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'location' => ['type' => 'geo_point'],
                ],
            ],
        ],
    ];

    /**
     * Overridden to always boot searchable.
     */
    public static function bootSearchable()
    {
        self::sourceBootSearchable();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'intro' => $this->intro,
            'description' => $this->description,
            'wait_time' => $this->wait_time,
            'is_free' => $this->is_free,
            'is_national' => $this->is_national,
            'status' => $this->status,
            'organisation_name' => $this->organisation->name,
            'taxonomy_categories' => $this->taxonomies()->pluck('name')->toArray(),
            'collection_categories' => static::collections($this)->where('type', Collection::TYPE_CATEGORY)->pluck('name')->toArray(),
            'collection_personas' => static::collections($this)->where('type', Collection::TYPE_PERSONA)->pluck('name')->toArray(),
            'service_locations' => $this->serviceLocations()
                ->with('location')
                ->get()
                ->map(function (ServiceLocation $serviceLocation) {
                    return [
                        'id' => $serviceLocation->id,
                        'location' => [
                            'lat' => $serviceLocation->location->lat,
                            'lon' => $serviceLocation->location->lon,
                        ],
                    ];
                })->toArray(),
        ];
    }

    /**
     * Custom logic for returning the data. Useful when wanting to transform
     * or modify the data before returning it, e.g. removing passwords.
     *
     * @param array $data
     * @return array
     */
    public function getData(array $data): array
    {
        return $data;
    }

    /**
     * Ensures conditional fields are reset to expected values.
     *
     * @return \App\Models\Service
     */
    public function resetConditionalFields(): Service
    {
        if ($this->is_free) {
            $this->update([
                'fees_text' => null,
                'fees_url' => null,
            ]);
        }

        if ($this->referral_method === static::REFERRAL_METHOD_NONE) {
            $this->update([
                'referral_button_text' => null,
                'referral_email' => null,
                'referral_url' => null,
                'show_referral_disclaimer' => false,
            ]);
        }

        if ($this->referral_method === static::REFERRAL_METHOD_INTERNAL) {
            $this->update(['referral_url' => null]);
        }

        if ($this->referral_method === static::REFERRAL_METHOD_EXTERNAL) {
            $this->update(['referral_email' => null]);
        }

        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $taxonomies
     * @return \App\Models\Service
     */
    public function syncServiceTaxonomies(EloquentCollection $taxonomies): Service
    {
        // Delete all existing service taxonomies.
        $this->serviceTaxonomies()->delete();

        // Create a service taxonomy record for each taxonomy and their parents.
        foreach ($taxonomies as $taxonomy) {
            $this->createServiceTaxonomy($taxonomy);
        }

        return $this;
    }

    /**
     * @param \App\Models\Taxonomy $taxonomy
     * @return \App\Models\ServiceTaxonomy
     */
    protected function createServiceTaxonomy(Taxonomy $taxonomy): ServiceTaxonomy
    {
        $hasParent = $taxonomy->parent !== null;
        $parentIsNotTopLevel = $taxonomy->parent->id !== Taxonomy::category()->id;

        if ($hasParent && $parentIsNotTopLevel) {
            $this->createServiceTaxonomy($taxonomy->parent);
        }

        return $this->serviceTaxonomies()->updateOrCreate(['taxonomy_id' => $taxonomy->id]);
    }

    /**
     * @param \App\Emails\Email $email
     */
    public function sendEmailToContact(Email $email)
    {
        Notification::sendEmail($email, $this);
    }

    /**
     * @param \App\Sms\Sms $sms
     */
    public function sendSmsToContact(Sms $sms)
    {
        Notification::sendSms($sms, $this);
    }

    /**
     * @param string $waitTime
     * @return bool
     */
    public static function waitTimeIsValid(string $waitTime): bool
    {
        return in_array($waitTime, [
            static::WAIT_TIME_ONE_WEEK,
            static::WAIT_TIME_TWO_WEEKS,
            static::WAIT_TIME_THREE_WEEKS,
            static::WAIT_TIME_MONTH,
            static::WAIT_TIME_LONGER,
        ]);
    }

    /**
     * @return bool
     */
    public function hasLogo(): bool
    {
        return $this->logo_file_id !== null;
    }

    /**
     * @param int|null $maxDimension
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\InvalidArgumentException
     * @return \App\Models\File|\Illuminate\Http\Response|\Illuminate\Contracts\Support\Responsable
     */
    public static function placeholderLogo(int $maxDimension = null)
    {
        if ($maxDimension !== null) {
            return File::resizedPlaceholder($maxDimension, File::META_PLACEHOLDER_FOR_SERVICE);
        }

        return response()->make(
            Storage::disk('local')->get('/placeholders/service.png'),
            Response::HTTP_OK,
            ['Content-Type' => File::MIME_TYPE_PNG]
        );
    }
}
