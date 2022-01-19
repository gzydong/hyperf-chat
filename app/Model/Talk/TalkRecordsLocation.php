<?php
declare(strict_types=1);

namespace App\Model\Talk;

use App\Model\BaseModel;

/**
 * Class TalkRecordsLocation
 *
 * @package App\Model\Talk
 */
class TalkRecordsLocation extends BaseModel
{
    protected $table = 'talk_records_location';

    protected $fillable = [
        'record_id',
        'user_id',
        'longitude',
        'latitude',
        'created_at',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'user_id'   => 'integer',
    ];
}
