<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Exceptions;

use Exception;
use Throwable;

class ParserFailedException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Parser failed: '.$message, $code, $previous);
    }
}
