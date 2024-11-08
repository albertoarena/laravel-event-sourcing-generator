<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Exceptions;

use Exception;
use Throwable;

class MigrationInvalidPrimaryKeyException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message ?: 'Primary key is not valid.', $code, $previous);
    }
}
