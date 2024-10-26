<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Exceptions;

use Exception;
use Throwable;

class UpdateMigrationIsNotSupportedException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Update migration file is not supported.', $code, $previous);
    }
}
