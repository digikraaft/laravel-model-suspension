<?php


namespace Digikraaft\ModelSuspension\Events;

use Digikraaft\ModelSuspension\Suspension;
use Illuminate\Database\Eloquent\Model;

class ModelSuspensionChanged
{
    /** @var \Digikraaft\ModelSuspension\Suspension */
    public Suspension $suspension;

    /** @var \Illuminate\Database\Eloquent\Model */
    public Model $model;

    public function __construct(Model $model, Suspension $suspension)
    {
        $this->suspension = $suspension;

        $this->model = $model;
    }
}
