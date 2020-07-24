<?php

namespace Digikraaft\ModelSuspension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Suspension extends Model
{
    protected $guarded = [];

    protected $table = 'suspensions';

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
