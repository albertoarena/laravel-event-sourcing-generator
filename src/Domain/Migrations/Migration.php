<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Migrations;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\MigrationParser;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperties;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\MigrationDoesNotExistException;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\ParserFailedException;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Migration
{
    protected ?string $primary;

    protected MigrationCreateProperties $properties;

    protected MigrationCreateProperties $ignored;

    /**
     * @throws Exception
     */
    public function __construct(
        protected string $path
    ) {
        $this->primary = null;
        $this->properties = new MigrationCreateProperties;
        $this->ignored = new MigrationCreateProperties;
        $this->parse();
    }

    /**
     * @throws MigrationDoesNotExistException
     */
    protected function getMigration(): ?string
    {
        if (File::exists($this->path)) {
            return File::get($this->path);
        } else {
            $files = File::files(database_path('migrations'));
            foreach ($files as $file) {
                $filename = $file->getFilename();
                if (Str::contains($filename, $this->path)) {
                    return $file->getContents();
                }
            }
        }

        throw new MigrationDoesNotExistException;
    }

    /**
     * @throws MigrationDoesNotExistException
     * @throws ParserFailedException
     */
    protected function parse(): void
    {
        $parser = (new MigrationParser($this->getMigration()))->parse();
        $this->properties->import($parser->getProperties());
        $this->ignored->import($parser->getIgnored());
        $this->primary = $this->properties->primary()->name;
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
}
