<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\Models;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class StubCallback
{
    public function __construct(
        protected Closure $closure
    ) {}

    /**
     * @throws FileNotFoundException
     */
    public function call(
        string $stubPath,
        string $outputPath,
    ): void {
        call_user_func($this->closure, $stubPath, $outputPath);
    }
}
