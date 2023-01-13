<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Determines if the primary key is a UUID.
     *
     * @var bool
     */
    protected $keyIsUuid = true;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->perPage = config('local.pagination_results');
    }

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->keyIsUuid && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = uuid();
            }
        });
    }

    /**
     * @param  string  $name
     * @return bool
     */
    public function hasAppend(string $name): bool
    {
        return in_array($name, $this->appends);
    }

    /**
     * Strips all non alpha numeric characters from a string
     *
     * @param string $string
     * @return string
     * @author
     **/
    public function onlyAlphaNumeric($string)
    {
        return preg_replace('/[^\w\d\s]+/i', '', $string);
    }
}
