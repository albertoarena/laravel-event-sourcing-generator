<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Helpers;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\MigrationParser;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\MigrationDoesNotExistException;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ParseMigration
{
    protected ?string $primary;

    /** @var MigrationCreateProperty[] */
    protected array $properties;

    /**
     * @throws Exception
     */
    public function __construct(
        protected string $path
    ) {
        $this->primary = null;
        $this->properties = [];
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
                if (preg_match("/$this->path/", $filename)) {
                    return $file->getContents();
                }
            }
        }

        throw new MigrationDoesNotExistException;
    }

    /**
     * @throws MigrationDoesNotExistException
     */
    protected function parse(): void
    {
        $this->properties = (new MigrationParser($this->getMigration()))->parse()->getProperties();

        $primary = Arr::where($this->properties, function (MigrationCreateProperty $property) {
            return $property->name === 'id' || $property->name === 'uuid';
        })[0] ?? Arr::first($this->properties);

        $this->primary = $primary->name;
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
        return $this->properties;
    }
}
