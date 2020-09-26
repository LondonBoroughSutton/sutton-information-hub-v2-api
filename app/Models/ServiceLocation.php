<?php

namespace App\Models;

use App\Models\Mutators\ServiceLocationMutators;
use App\Models\Relationships\ServiceLocationRelationships;
use App\Models\Scopes\ServiceLocationScopes;
use App\Support\Time;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

class ServiceLocation extends Model
{
    use ServiceLocationMutators;
    use ServiceLocationRelationships;
    use ServiceLocationScopes;

    /**
     * Determine if the service location is open at this point in time.
     *
     * @return bool
     */
    public function isOpenNow(): bool
    {
        // First check if any holiday opening hours have been specified.
        $hasHolidayHoursOpenNow = $this->hasHolidayHoursOpenNow();

        // If holiday opening hours found, then return them.
        if ($hasHolidayHoursOpenNow !== null) {
            return $hasHolidayHoursOpenNow;
        }

        // If no holiday hours found, then resort to regular opening hours.
        return $this->hasRegularHoursOpenNow();
    }

    /**
     * Returns true if open, false if closed, or null if not specified.
     *
     * @return bool|null
     */
    protected function hasHolidayHoursOpenNow(): ?bool
    {
        // Get the holiday opening hours that today falls within.
        $holidayOpeningHour = $this->holidayOpeningHours()
            ->where('starts_at', '<=', Date::today())
            ->where('ends_at', '>=', Date::today())
            ->first();

        // If none found, return null.
        if ($holidayOpeningHour === null) {
            return null;
        }

        // If closed, opening and closing time are redundant, so just return false.
        if ($holidayOpeningHour->is_closed) {
            return false;
        }

        // Return if the current time falls within the opening and closing time.
        return Time::now()->between($holidayOpeningHour->opens_at, $holidayOpeningHour->closes_at);
    }

    /**
     * @return bool
     */
    protected function hasRegularHoursOpenNow(): bool
    {
        // Loop through each opening hour.
        foreach ($this->regularOpeningHours as $regularOpeningHour) {
            // Check if the current time falls within the opening hours.
            $isOpenNow = Time::now()->between($regularOpeningHour->opens_at, $regularOpeningHour->closes_at);

            // If not, then continue to the next opening hour.
            if (!$isOpenNow) {
                continue;
            }

            // Use a different algorithm for each frequency type.
            switch ($regularOpeningHour->frequency) {
                // If weekly then check that the weekday is the same as today.
                case RegularOpeningHour::FREQUENCY_WEEKLY:
                    if (Date::today()->dayOfWeek === $regularOpeningHour->weekday) {
                        return true;
                    }
                    break;
                // If monthly then check that the day of the month is the same as today.
                case RegularOpeningHour::FREQUENCY_MONTHLY:
                    if (Date::today()->day === $regularOpeningHour->day_of_month) {
                        return true;
                    }
                    break;
                // If fortnightly then check that today falls directly on a multiple of 2 weeks.
                case RegularOpeningHour::FREQUENCY_FORTNIGHTLY:
                    if (fmod(Date::today()->diffInDays($regularOpeningHour->starts_at) / CarbonImmutable::DAYS_PER_WEEK, 2) === 0.0) {
                        return true;
                    }
                    break;
                // If nth occurrence of month then check today is the same occurrence.
                case RegularOpeningHour::FREQUENCY_NTH_OCCURRENCE_OF_MONTH:
                    $occurrence = occurrence($regularOpeningHour->occurrence_of_month);
                    $weekday = weekday($regularOpeningHour->weekday);
                    $month = month(Date::today()->month);
                    $year = Date::today()->year;
                    $dateString = "$occurrence $weekday of $month $year";
                    $date = Date::createFromTimestamp(strtotime($dateString));
                    if (Date::today()->equalTo($date)) {
                        return true;
                    }
                    break;
            }
        }

        return false;
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
     * @return \App\Models\ServiceLocation
     */
    public function touchService(): ServiceLocation
    {
        $this->service->save();

        return $this;
    }

    /**
     * @param int|null $maxDimension
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\InvalidArgumentException
     * @return \App\Models\File|\Illuminate\Http\Response|\Illuminate\Contracts\Support\Responsable
     */
    public static function placeholderImage(int $maxDimension = null)
    {
        if ($maxDimension !== null) {
            return File::resizedPlaceholder($maxDimension, File::META_PLACEHOLDER_FOR_SERVICE_LOCATION);
        }

        return response()->make(
            Storage::disk('local')->get('/placeholders/service_location.png'),
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
}
