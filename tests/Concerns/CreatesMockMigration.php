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
        array $updateProperties = [],
        array $dropProperties = [],
        array $renameProperties = [],
        ?string $migrationName = null,
    ): ?string {
        $tableName = Str::plural($tableName);
        if (! $migrationName) {
            $migrationName = 'update_'.$tableName.'_table';
        }
        $this->withoutMockingConsoleOutput()
            ->artisan('make:migration', ['name' => $migrationName]);

        $output = Artisan::output();
        $this->mockConsoleOutput = true;

        if (preg_match('/INFO\s*Migration\s*\[(.*)] created successfully./', $output, $matches)) {
            $migration = $matches[1];

            // Load file
            $migrationFile = File::get(base_path($migration));

            // Check if migration contains Schema::table
            $containsSchemaTable = str_contains($migrationFile, "Schema::table('$tableName', function (Blueprint \$table) {");

            $indent = Str::repeat(' ', 4);
            $upInject = [];
            $downInject = [];

            // Inject update table and properties
            if (! $containsSchemaTable) {
                $upInject[] = "Schema::table('$tableName', function (Blueprint \$table) {";
                $downInject[] = "Schema::table('$tableName', function (Blueprint \$table) {";
            }

            // Update
            foreach ($updateProperties as $name => $type) {
                $nullable = Str::startsWith($type, '?') ? '->nullable()' : '';
                $type = $nullable ? Str::after($type, '?') : $type;
                $upInject[] = "$indent\$table->".$this->builtInTypeToColumnType($type)."('$name')$nullable;";
                $downInject[] = "$indent\$table->dropColumn('$name');";
            }

            // Drop
            foreach ($dropProperties as $name) {
                if (is_array($name)) {
                    $name = implode(', ', array_map(fn ($v) => "'$v'", $name));
                    $upInject[] = "$indent\$table->dropColumn([$name]);";
                } else {
                    $upInject[] = "$indent\$table->dropColumn('$name');";
                }
            }

            // Rename
            foreach ($renameProperties as $oldName => $newName) {
                $upInject[] = "$indent\$table->renameColumn('$oldName', '$newName');";
            }

            if (! $containsSchemaTable) {
                $upInject[] = '});';
                $upInject[] = "\n";
            }

            $upInject = implode("\n", Arr::map($upInject, fn ($line) => "$indent$indent$line"));
            if ($containsSchemaTable) {
                $pattern = "/public function up\(\): void\n\s*{\n\s*Schema::table\('.*',\s*function\s*\(Blueprint\s*\\\$table\)\s*{\n\s*\/\/\n/";
                $replacement = "public function up(): void\n$indent{\n$indent{$indent}Schema::table('animals', function (Blueprint \$table) {\n$upInject\n";
            } else {
                $pattern = "/public function up\(\): void\n\s*{\n\s*\/\/\n/";
                $replacement = "public function up(): void\n$indent{\n$upInject";
            }

            $newCode = preg_replace(
                $pattern,
                $replacement,
                $migrationFile
            );

            // Add drop columns to down section
            if (! $containsSchemaTable) {
                $downInject[] = '});';
                $downInject[] = "\n";
            }
            $downInject = implode("\n", Arr::map($downInject, fn ($line) => "$indent$indent$line"));
            if ($containsSchemaTable) {
                $pattern = "/public function down\(\): void\n\s*{\n\s*Schema::table\('.*',\s*function\s*\(Blueprint\s*\\\$table\)\s*{\n\s*\/\/\n/";
                $replacement = "public function down(): void\n$indent{\n$indent{$indent}Schema::table('animals', function (Blueprint \$table) {\n$downInject\n";
            } else {
                $pattern = "/public function down\(\): void\n\s*{\n\s*\/\/\n/";
                $replacement = "public function down(): void\n$indent{\n$downInject";
            }

            $newCode = preg_replace(
                $pattern,
                $replacement,
                $newCode
            );

            // Save file with new properties
            File::put(base_path($migration), $newCode);

            return realpath(base_path($migration));
        }

        return null;
    }
}
