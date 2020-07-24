<?php

namespace Digikraaft\ModelSuspension\Exceptions;

use Exception;

class InvalidSuspensionModel extends Exception
{
    public static function create(string $model): self
    {
        return new self("The model `{$model}` is invalid. A valid model must extend the model \Digikraaft\ModelSuspension\Suspension.");
    }
}
