<?php

namespace Tests\Concerns;

use Albertoarena\LaravelDomainGenerator\Domain\PhpParser\MigrationParser;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait CreatesMockMigration
{
    /**
     * @throws Exception
     */
    protected function createMockMigration(
        string $tableName,
        array $modelProperties,
        array $options = [
            ':primary' => 'uuid',
        ],
    ): ?string {
        $this->withoutMockingConsoleOutput()
            ->artisan('make:migration', ['name' => 'create_'.Str::plural($tableName).'_table']);

        $output = Artisan::output();
        $this->mockConsoleOutput = true;

        if (preg_match('/INFO\s*Migration\s*\[(.*)] created successfully./', $output, $matches)) {
            $migration = $matches[1];

            // Load file
            $migrationFile = File::get(base_path($migration));

            // Parse file and inject properties
            $newCode = (new MigrationParser($migrationFile))->modify($modelProperties, $options);

            // Save file with new properties
            File::put(base_path($migration), $newCode);

            return realpath(base_path($migration));
        }

        return null;
    }
}
