<?php

namespace Tests\Concerns;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models\CommandSettings;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Migrations\Migration;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubReplacer;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubResolver;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait AssertsDomainGenerated
{
    use HasBlueprintColumnType;

    protected function getExpectedFiles(
        string $model,
        string $domain,
        string $namespace,
        bool $createAggregateRoot,
        bool $createReactor,
        bool $createUnitTest
    ): array {
        $unexpectedFiles = [];
        $expectedFiles = [
            "$namespace/$domain/Actions/Create$model.php" => $createAggregateRoot ?
                'actions.create.with-aggregate-root.stub' :
                'actions.create.without-aggregate-root.stub',
            "$namespace/$domain/Actions/Delete$model.php" => $createAggregateRoot ?
                'actions.delete.with-aggregate-root.stub' :
                'actions.delete.without-aggregate-root.stub',
            "$namespace/$domain/Actions/Update$model.php" => $createAggregateRoot ?
                'actions.update.with-aggregate-root.stub' :
                'actions.update.without-aggregate-root.stub',
            "$namespace/$domain/DataTransferObjects/{$model}Data.php" => 'data-transfer-object.stub',
            "$namespace/$domain/Events/{$model}Created.php" => 'events.created.stub',
            "$namespace/$domain/Events/{$model}Deleted.php" => 'events.deleted.stub',
            "$namespace/$domain/Events/{$model}Updated.php" => 'events.updated.stub',
            "$namespace/$domain/Projections/{$model}.php" => 'projection.stub',
            "$namespace/$domain/Projectors/{$model}Projector.php" => 'projector.stub',
        ];

        if ($createAggregateRoot) {
            $expectedFiles["$namespace/$domain/{$model}AggregateRoot.php"] = 'aggregate-root.stub';
        } else {
            $unexpectedFiles["$namespace/$domain/{$model}AggregateRoot.php"] = 'aggregate-root.stub';
        }

        if ($createReactor) {
            $expectedFiles["$namespace/$domain/Reactors/{$model}Reactor.php"] = 'reactor.stub';
        } else {
            $unexpectedFiles["$namespace/$domain/Reactors/{$model}Reactor.php"] = 'reactor.stub';
        }

        if ($createUnitTest) {
            // tests/Domain/World/HelloTest.php
            $expectedFiles["tests/$namespace/$domain/{$model}Test.php"] = 'test.stub';
        } else {
            $unexpectedFiles["tests/$namespace/$domain/{$model}Test.php"] = 'test.stub';
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
        bool $createUnitTest = false,
    ): void {
        if (! $useUuid) {
            $createAggregateRoot = false;
        }

        [$expectedFiles, $unexpectedFiles] = $this->getExpectedFiles(
            model: $model,
            domain: $domain ?? $model,
            namespace: $namespace,
            createAggregateRoot: $createAggregateRoot,
            createReactor: $createReactor,
            createUnitTest: $createUnitTest,
        );

        // Assert that the files were created
        foreach (array_keys($expectedFiles) as $generatedFile) {
            if (Str::startsWith($generatedFile, 'tests')) {
                $this->assertTrue(File::exists(base_path($generatedFile)));
            } else {
                $this->assertTrue(File::exists(app_path($generatedFile)));
            }
        }

        // Load existing migration
        if ($migration) {
            try {
                $useUuid = (new Migration($migration))->primary() === 'uuid';
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
            createUnitTest: $createUnitTest,
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

            $isTest = Str::startsWith($generatedFile, 'tests');

            // Load generated file
            $generated = File::get($isTest ?
                base_path($generatedFile) :
                app_path($generatedFile)
            );

            // Assert namespace
            $baseNamespace = $isTest ? 'Tests' : 'App';
            $this->assertStringContainsString('namespace '.$baseNamespace.'\\'.$stubReplacer->settings->namespace.'\\'.$stubReplacer->settings->domain, $generated);

            // Assert specific expectations
            if ($stubFile === 'data-transfer-object.stub') {
                if (! $modelProperties) {
                    $this->assertMatchesRegularExpression("/\/\/ Add here public properties, e.g.:/", $generated);
                } else {
                    // Assert that model properties are using camel format
                    foreach ($settings->modelProperties->toArray() as $property) {
                        $name = Str::camel($property->name);

                        $type = $property->type->toNormalisedBuiltInType();
                        $nullable = $property->type->nullable ? '\\?' : '';

                        $this->assertMatchesRegularExpression("/public $nullable$type \\$$name/", $generated);
                    }
                }
            } elseif ($stubFile === 'projection.stub') {
                if ($useUuid) {
                    $this->assertMatchesRegularExpression("/'uuid' => 'string',/", $generated);
                } else {
                    $this->assertMatchesRegularExpression("/'id' => 'int',/", $generated);
                }
                foreach ($settings->modelProperties->toArray() as $property) {
                    $type = $property->type->toProjection();

                    $this->assertMatchesRegularExpression("/'$property->name' => '$type'/", $generated);
                }
            } elseif ($stubFile === 'projector.stub') {
                if ($useUuid) {
                    $this->assertMatchesRegularExpression("/'uuid' => \\\$event->{$stubReplacer->settings->nameAsPrefix}Uuid/", $generated);
                } else {
                    $this->assertDoesNotMatchRegularExpression("/'id' => \\\$event->{$stubReplacer->settings->nameAsPrefix}Id/", $generated);
                }
                // Assert that model properties are using camel format
                foreach ($settings->modelProperties->toArray() as $item) {
                    $name = Str::camel($item->name);
                    $this->assertMatchesRegularExpression("/'$item->name'\s=>\s\\\$event->{$stubReplacer->settings->nameAsPrefix}Data->$name/", $generated);
                }
            } elseif ($stubFile === 'test.stub') {
                if ($createUnitTest) {
                    $modelLower = Str::lower($settings->model);
                    $this->assertMatchesRegularExpression("/protected function fakeData\(\): {$settings->model}Data/", $generated);
                    $this->assertMatchesRegularExpression("/public function can_create_{$modelLower}_model/", $generated);
                    $this->assertMatchesRegularExpression("/public function can_update_{$modelLower}_model/", $generated);
                    $this->assertMatchesRegularExpression("/public function can_delete_{$modelLower}_model/", $generated);
                    // Assert that model properties are using camel format
                    foreach ($settings->modelProperties->toArray() as $item) {
                        $this->assertMatchesRegularExpression('/\\$this->assertEquals\(\\$data->'.Str::camel($item->name).", \\\$record->$item->name\)/", $generated);
                        $this->assertMatchesRegularExpression('/\\$this->assertEquals\(\\$updateData->'.Str::camel($item->name).", \\\$updatedRecord->$item->name\)/", $generated);
                    }
                }
            }
        }

        // Assert files that must not exist
        foreach (array_keys($unexpectedFiles) as $generatedFile) {
            $this->assertFalse(File::exists(app_path($generatedFile)));
        }
    }
}
