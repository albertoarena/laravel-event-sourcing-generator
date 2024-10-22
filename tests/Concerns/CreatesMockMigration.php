<?php

namespace Tests\Concerns;

use Albertoarena\LaravelDomainGenerator\Domain\PhpParser\MigrationParser;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

trait CreatesMockMigration
{
    /**
     * @throws Exception
     */
    protected function createMockMigration(string $tableName, array $modelProperties, string $primaryKey): ?string
    {
        $this->withoutMockingConsoleOutput()
            ->artisan('make:migration', ['name' => 'create_'.$tableName.'_table']);

        $output = Artisan::output();
        $this->mockConsoleOutput = true;

        if (preg_match('/INFO\s*Migration\s*\[(.*)] created successfully./', $output, $matches)) {
            $migration = $matches[1];
            $migrationFile = File::get(base_path($migration));

            // Parse file
            $migrationParser = new MigrationParser($migrationFile);

            // Inject properties
            $newCode = $migrationParser->injectProperties($modelProperties, $primaryKey);

            File::put(base_path($migration), $newCode);

            return realpath(base_path($migration));
        }

        return null;
    }
}
