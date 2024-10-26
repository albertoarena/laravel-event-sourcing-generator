<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Exceptions;

use Exception;
use Throwable;

class MigrationDoesNotExistException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Migration file does not exist.', $code, $previous);
    }
}
