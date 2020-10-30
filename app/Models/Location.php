<?php

namespace App\Models;

use App\Contracts\Geocoder;
use App\Models\Mutators\LocationMutators;
use App\Models\Relationships\LocationRelationships;
use App\Models\Scopes\LocationScopes;
use App\Support\Address;
use App\Support\Coordinate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class Location extends Model
{
    use LocationMutators;
    use LocationRelationships;
    use LocationScopes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'has_wheelchair_access' => 'boolean',
        'has_induction_loop' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return \App\Models\Location
     */
    public function updateCoordinate(): self
    {
        /**
         * @var \App\Contracts\Geocoder
         */
        $geocoder = resolve(Geocoder::class);
        $coordinate = $geocoder->geocode($this->toAddress());

        $this->lat = $coordinate->lat();
        $this->lon = $coordinate->lon();

        return $this;
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
     * @return \App\Models\Location
     */
    public function touchServices(): Location
    {
        $this->services()->get()->each->save();

        return $this;
    }

    /**
     * @return \App\Support\Address
     */
    public function toAddress(): Address
    {
        return Address::create(
            [$this->address_line_1, $this->address_line_2, $this->address_line_3],
            $this->city,
            $this->county,
            $this->postcode,
            $this->country
        );
    }

    /**
     * @return \App\Support\Coordinate
     */
    public function toCoordinate(): Coordinate
    {
        return new Coordinate($this->lat, $this->lon);
    }

    /**
     * @param int|null $maxDimension
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\InvalidArgumentException
     * @return \App\Models\File|\Illuminate\Http\Response|\Illuminate\Contracts\Support\Responsable
     */
    public static function placeholderImage(int $maxDimension = null)
    {
        if ($maxDimension !== null) {
            return File::resizedPlaceholder($maxDimension, File::META_PLACEHOLDER_FOR_LOCATION);
        }

        return response()->make(
            Storage::disk('local')->get('/placeholders/location.png'),
            Response::HTTP_OK,
            ['Content-Type' => File::MIME_TYPE_PNG]
        );
    }

    /**
     * @return bool
     */
    public function hasImage(): bool
    {
        return $this->image_file_id !== null;
    }

    /**
     * Checks for dependant relationships, if noe found removes the Location
     *
     * @return null
     **/
    public function safeDelete()
    {
        if ($this->serviceLocations()->doesntExist() && $this->organisation()->doesntExist() && $this->users()->doesntExist()) {
            return $this->delete();
        }
        return false;
    }
}
