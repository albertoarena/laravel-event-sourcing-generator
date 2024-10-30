<?php

namespace Tests\Concerns;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\Domain\Migrations\Contracts\MigrationOptionInterface;
use Tests\Domain\Migrations\ModifyMigration;

trait CreatesMockMigration
{
    /**
     * @throws Exception
     */
    protected function createMockCreateMigration(
        string $tableName,
        array $modelProperties,
        array $options = [
            MigrationOptionInterface::PRIMARY_KEY => 'uuid',
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
            $newCode = (new ModifyMigration($migrationFile, $modelProperties, $options))->modify();

            // Save file with new properties
            File::put(base_path($migration), $newCode);

            return realpath(base_path($migration));
        }

        return null;
    }

    protected function createMockUpdateMigration(
        string $tableName,
        array $modelProperties,
    ): ?string {
        $tableName = Str::plural($tableName);
        $this->withoutMockingConsoleOutput()
            ->artisan('make:migration', ['name' => 'update_'.$tableName.'_table']);

        $output = Artisan::output();
        $this->mockConsoleOutput = true;

        if (preg_match('/INFO\s*Migration\s*\[(.*)] created successfully./', $output, $matches)) {
            $migration = $matches[1];

            // Load file
            $migrationFile = File::get(base_path($migration));

            $indent = Str::repeat(' ', 4);

            // Inject update table and properties
            $inject[] = "Schema::table('$tableName', function (Blueprint \$table) {";
            foreach ($modelProperties as $name => $type) {
                $nullable = Str::startsWith($type, '?') ? '->nullable()' : '';
                $type = $nullable ? Str::after($type, '?') : $type;
                $inject[] = "$indent\$table->".$this->builtInTypeToColumnType($type)."('$name')$nullable;";
            }
            $inject[] = "});\n";
            $inject = implode("\n", Arr::map($inject, fn ($line) => "$indent$indent$line"));
            $newCode = preg_replace(
                "/public function up\(\): void\n\s*{\n\s*\/\/\n/",
                "public function up(): void\n$indent{\n$inject",
                $migrationFile
            );

            // Save file with new properties
            File::put(base_path($migration), $newCode);

            return realpath(base_path($migration));
        }

        return null;
    }
}
