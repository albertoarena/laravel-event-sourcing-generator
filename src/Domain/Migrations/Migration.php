<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Migrations;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\MigrationParser;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperties;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\MigrationDoesNotExistException;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\MigrationInvalidException;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\ParserFailedException;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Migration
{
    protected ?string $primary;

    protected MigrationCreateProperties $properties;

    protected MigrationCreateProperties $ignored;

    protected array $migrations;

    /**
     * @throws Exception
     */
    public function __construct(
        protected string $path
    ) {
        $this->primary = null;
        $this->properties = new MigrationCreateProperties;
        $this->ignored = new MigrationCreateProperties;
        $this->migrations = [];
        $this->parse();
    }

    /**
     * @throws MigrationDoesNotExistException
     * @throws MigrationInvalidException
     */
    protected function getMigrations(): array
    {
        $this->migrations = [];
        $found = [];
        if (File::exists($this->path)) {
            $this->migrations = [basename($this->path)];
            $found = [File::get($this->path)];
        } else {
            if (in_array(strtolower($this->path), ['create', 'update'])) {
                throw new MigrationInvalidException;
            }

            $files = File::files(database_path('migrations'));
            foreach ($files as $file) {
                $filename = $file->getFilename();
                if (Str::contains($filename, $this->path)) {
                    $this->migrations[] = basename($filename);
                    $found[] = $file->getContents();
                }
            }
        }

        if (! $found) {
            throw new MigrationDoesNotExistException;
        }

        return $found;
    }

    /**
     * @throws MigrationDoesNotExistException
     * @throws ParserFailedException
     * @throws MigrationInvalidException
     */
    protected function parse(): void
    {
        $found = $this->getMigrations();
        foreach ($found as $migration) {
            $parser = (new MigrationParser($migration))->parse();
            $this->properties->import($parser->getProperties(), reset: false);
            $this->ignored->import($parser->getIgnored(), reset: false);
            $this->primary = $this->properties->primary()->name;
        }
    }

    public function primary(): ?string
    {
        return $this->primary;
    }

    /**
     * @return MigrationCreateProperty[]
     */
    public function properties(): array
    {
        return $this->properties->toArray();
    }

    /**
     * @return MigrationCreateProperty[]
     */
    public function ignored(): array
    {
        return $this->ignored->withoutSkippedMethods()->toArray();
    }

    public function migrations(): array
    {
        return $this->migrations;
    }
}
