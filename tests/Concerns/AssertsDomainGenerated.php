<?php

namespace Tests\Concerns;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models\CommandSettings;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubReplacer;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubResolver;
use Albertoarena\LaravelEventSourcingGenerator\Helpers\ParseMigration;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait AssertsDomainGenerated
{
    use HasBlueprintColumnType;

    protected function getExpectedFiles(
        string $name,
        string $domain,
        string $baseRoot,
        bool $createAggregateRoot,
        bool $createReactor,
    ): array {
        $unexpectedFiles = [];
        $expectedFiles = [
            "$baseRoot/$domain/Actions/Create$name.php" => $createAggregateRoot ?
                'actions.create.with-aggregate-root.stub' :
                'actions.create.without-aggregate-root.stub',
            "$baseRoot/$domain/Actions/Delete$name.php" => $createAggregateRoot ?
                'actions.delete.with-aggregate-root.stub' :
                'actions.delete.without-aggregate-root.stub',
            "$baseRoot/$domain/Actions/Update$name.php" => $createAggregateRoot ?
                'actions.update.with-aggregate-root.stub' :
                'actions.update.without-aggregate-root.stub',
            "$baseRoot/$domain/DataTransferObjects/{$name}Data.php" => 'data-transfer-object.stub',
            "$baseRoot/$domain/Events/{$name}Created.php" => 'events.created.stub',
            "$baseRoot/$domain/Events/{$name}Deleted.php" => 'events.deleted.stub',
            "$baseRoot/$domain/Events/{$name}Updated.php" => 'events.updated.stub',
            "$baseRoot/$domain/Projections/{$name}.php" => 'projection.stub',
            "$baseRoot/$domain/Projectors/{$name}Projector.php" => 'projector.stub',
        ];
        if ($createAggregateRoot) {
            $expectedFiles["$baseRoot/$domain/{$name}AggregateRoot.php"] = 'aggregate-root.stub';
        } else {
            $unexpectedFiles["$baseRoot/$domain/{$name}AggregateRoot.php"] = 'aggregate-root.stub';
        }
        if ($createReactor) {
            $expectedFiles["$baseRoot/$domain/Reactors/{$name}Reactor.php"] = 'reactor.stub';
        } else {
            $unexpectedFiles["$baseRoot/$domain/Reactors/{$name}Reactor.php"] = 'reactor.stub';
        }

        return [
            $expectedFiles,
            $unexpectedFiles,
        ];
    }

    protected function assertDomainGenerated(
        string $model,
        ?string $domain = null,
        string $namespace = 'Domain',
        ?string $migration = null,
        bool $createAggregateRoot = true,
        bool $createReactor = true,
        bool $useUuid = true,
        array $modelProperties = [],
        int $indentation = 4,
    ): void {
        if (! $useUuid) {
            $createAggregateRoot = false;
        }

        [$expectedFiles, $unexpectedFiles] = $this->getExpectedFiles($model, $domain ?? $model, $namespace, $createAggregateRoot, $createReactor);

        // Assert that the files were created
        foreach (array_keys($expectedFiles) as $generatedFile) {
            $this->assertTrue(File::exists(app_path($generatedFile)));
        }

        // Load existing migration
        if ($migration) {
            try {
                $useUuid = (new ParseMigration($migration))->primary() === 'uuid';
            } catch (Exception) {
            }
        }

        // Create settings
        $settings = new CommandSettings(
            model: $model,
            domain: $domain ?? $model,
            namespace: $namespace,
            migration: $migration,
            createAggregateRoot: $createAggregateRoot,
            createReactor: $createReactor,
            indentation: $indentation,
            useUuid: $useUuid,
            nameAsPrefix: Str::lcfirst(Str::camel($model)),
            domainPath: '',
        );
        $settings->modelProperties->import($modelProperties);

        // Create stub replacer
        $stubReplacer = new StubReplacer($settings);

        // Assert content of files that must have been generated
        foreach ($expectedFiles as $generatedFile => $stubFile) {
            // Resolve stub
            [$stubFileResolved] = (new StubResolver('stubs/'.$stubFile, ''))
                ->resolve(app(), $settings);

            // Load stub
            $stub = File::get($stubFileResolved);

            // Replace content
            $stubReplacer
                ->replaceWithClosure($stub, 'class', fn () => $model)
                ->replace($stub);

            // Load generated file
            $generated = File::get(app_path($generatedFile));

            // Assert namespace
            $this->assertStringContainsString('namespace App\\'.$stubReplacer->settings->namespace.'\\'.$stubReplacer->settings->domain, $generated);

            // Assert specific expectations
            if ($stubFile === 'data-transfer-object.stub') {
                if (! $modelProperties) {
                    $this->assertMatchesRegularExpression("/\/\/ Add here public properties, e.g.:/", $generated);
                }
            } elseif ($stubFile === 'projection.stub') {
                if ($useUuid) {
                    $this->assertMatchesRegularExpression("/'uuid' => 'string',/", $generated);
                } else {
                    $this->assertMatchesRegularExpression("/'id' => 'int',/", $generated);
                }
                foreach ($settings->modelProperties->toArray() as $property) {
                    $type = $this->columnTypeToBuiltInType($property->type);
                    $type = $this->carbonToBuiltInType($type);
                    $type = Str::replaceFirst('?', '', $type);
                    $this->assertMatchesRegularExpression("/'$property->name' => '$type'/", $generated);
                }
            } elseif ($stubFile === 'projector.stub') {
                if ($useUuid) {
                    $this->assertMatchesRegularExpression("/'uuid' => \\\$event->{$stubReplacer->settings->nameAsPrefix}Uuid/", $generated);
                } else {
                    $this->assertDoesNotMatchRegularExpression("/'id' => \\\$event->{$stubReplacer->settings->nameAsPrefix}Id/", $generated);
                }
            }
        }

        // Assert files that must not exist
        foreach (array_keys($unexpectedFiles) as $generatedFile) {
            $this->assertFalse(File::exists(app_path($generatedFile)));
        }
    }
}
