<?php

declare (strict_types=1);

namespace App\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property \Carbon\Carbon $created_at
 */
class Emoticon extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'emoticon';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'created_at' => 'datetime'];
}
