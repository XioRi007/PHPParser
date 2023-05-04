<?php

namespace Core\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /**
     * The attributes that are mass assignable.
     * @var  array
     */
    protected $fillable = ['id', 'data', 'status'];

    /**
     * The table associated with the model.
     * @var  string
     */
    protected $table = 'queue';

    /**
     * The attributes that should be cast.
     * @var  array
     */
    protected $casts = [
        'id' => 'string',
    ];

    /**
     * Indicates if the model should be timestamped.
     * @var  bool
     */
    public $timestamps = false;

    /**
     * Decodes data attribute from json
     * @param  $value
     * @return  mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Encodes data attribute to json
     * @param  $value
     * @return  void
     */
    public function setDataAttribute($value): void
    {
        $this->attributes['data'] = json_encode($value);
    }
}
