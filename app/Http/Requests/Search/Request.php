<?php

namespace App\Http\Requests\Search;

use App\Contracts\Search;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'query' => [
                'required_without_all:category,type,persona,wait_time,is_free,is_national,location',
                'string',
                'min:3',
                'max:255',
            ],
            'type' => [
                'required_without_all:query,category,persona,wait_time,is_free,is_national,location',
                Rule::in([
                    Service::TYPE_SERVICE,
                    Service::TYPE_ACTIVITY,
                    Service::TYPE_CLUB,
                    Service::TYPE_GROUP,
                    Service::TYPE_HELPLINE,
                    Service::TYPE_INFORMATION,
                    Service::TYPE_APP,
                    Service::TYPE_ADVICE,
                ]),
            ],
            'category' => [
                'required_without_all:query,type,persona,wait_time,is_free,is_national,location',
                'string',
                'min:1',
                'max:255',
            ],
            'persona' => [
                'required_without_all:query,type,category,wait_time,is_free,is_national,location',
                'string',
                'min:1',
                'max:255',
            ],
            'wait_time' => [
                'required_without_all:query,type,category,is_free,is_national,persona,location',
                Rule::in([
                    Service::WAIT_TIME_ONE_WEEK,
                    Service::WAIT_TIME_TWO_WEEKS,
                    Service::WAIT_TIME_THREE_WEEKS,
                    Service::WAIT_TIME_MONTH,
                    Service::WAIT_TIME_LONGER,
                ]),
            ],
            'is_free' => [
                'required_without_all:query,type,category,persona,wait_time,location,is_national',
                'boolean',
            ],
            'is_national' => [
                'required_without_all:query,type,category,persona,wait_time,location,is_free',
                'boolean',
            ],
            'order' => [
                Rule::in([Search::ORDER_RELEVANCE, Search::ORDER_DISTANCE]),
            ],
            'location' => [
                'required_without_all:query,type,category,persona,wait_time,is_free,is_national',
                'required_if:order,distance',
                'array',
            ],
            'location.lat' => [
                'required_with:location',
                'numeric',
                'min:-90',
                'max:90',
            ],
            'location.lon' => [
                'required_with:location',
                'numeric',
                'min:-180',
                'max:180',
            ],
            'distance' => [
                'integer',
                'min:0',
            ],
        ];
    }
}
