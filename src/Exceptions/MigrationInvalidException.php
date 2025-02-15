<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Exceptions;

use Exception;
use Throwable;

class MigrationInvalidException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message ?: 'Migration file is invalid (create and update words are reserved).', $code, $previous);
    }
}
