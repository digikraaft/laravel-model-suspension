<?php

namespace Digikraaft\ModelSuspension\Tests\Models;

use Digikraaft\ModelSuspension\CanBeSuspended;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use CanBeSuspended;

    protected $guarded = [];
}
