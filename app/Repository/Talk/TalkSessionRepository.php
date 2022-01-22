<?php
declare(strict_types=1);

namespace App\Repository\Talk;

use App\Model\Talk\TalkSession;
use App\Repository\BaseRepository;

class TalkSessionRepository extends BaseRepository
{
    public function __construct(TalkSession $model)
    {
        parent::__construct($model);
    }
}
