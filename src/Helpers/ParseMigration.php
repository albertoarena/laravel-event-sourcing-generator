<?php

namespace Albertoarena\LaravelDomainGenerator\Helpers;

use Albertoarena\LaravelDomainGenerator\Domain\PhpParser\MigrationParser;
use Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Exception;
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
     * @throws Exception
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

        throw new Exception('Migration file does not exist');
    }

    /**
     * @throws Exception
     */
    protected function parse(): void
    {
        $this->properties = (new MigrationParser($this->getMigration()))->parse()->getProperties();

        $this->primary = $this->properties[0]->name;
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
