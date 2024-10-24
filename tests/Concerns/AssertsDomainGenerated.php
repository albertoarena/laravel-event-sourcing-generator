<?php

namespace Tests\Concerns;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Commands\CommandSettings;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubReplacer;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubResolver;
use Albertoarena\LaravelEventSourcingGenerator\Helpers\ParseMigration;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait AssertsDomainGenerated
{
    protected function getExpectedFiles(
        string $domain,
        string $domainBaseRoot,
        bool $createAggregateRoot,
        bool $createReactor,
    ): array {
        $unexpectedFiles = [];
        $expectedFiles = [
            "$domainBaseRoot/$domain/Actions/Create$domain.php" => $createAggregateRoot ?
                'actions.create.with-aggregate-root.stub' :
                'actions.create.without-aggregate-root.stub',
            "$domainBaseRoot/$domain/Actions/Delete$domain.php" => $createAggregateRoot ?
                'actions.delete.with-aggregate-root.stub' :
                'actions.delete.without-aggregate-root.stub',
            "$domainBaseRoot/$domain/Actions/Update$domain.php" => $createAggregateRoot ?
                'actions.update.with-aggregate-root.stub' :
                'actions.update.without-aggregate-root.stub',
            "$domainBaseRoot/$domain/DataTransferObjects/{$domain}Data.php" => 'data-transfer-object.stub',
            "$domainBaseRoot/$domain/Events/{$domain}Created.php" => 'events.created.stub',
            "$domainBaseRoot/$domain/Events/{$domain}Deleted.php" => 'events.deleted.stub',
            "$domainBaseRoot/$domain/Events/{$domain}Updated.php" => 'events.updated.stub',
            "$domainBaseRoot/$domain/Projections/{$domain}.php" => 'projection.stub',
            "$domainBaseRoot/$domain/Projectors/{$domain}Projector.php" => 'projector.stub',
        ];
        if ($createAggregateRoot) {
            $expectedFiles["$domainBaseRoot/$domain/{$domain}AggregateRoot.php"] = 'aggregate-root.stub';
        } else {
            $unexpectedFiles["$domainBaseRoot/$domain/{$domain}AggregateRoot.php"] = 'aggregate-root.stub';
        }
        if ($createReactor) {
            $expectedFiles["$domainBaseRoot/$domain/Reactors/{$domain}Reactor.php"] = 'reactor.stub';
        } else {
            $unexpectedFiles["$domainBaseRoot/$domain/Reactors/{$domain}Reactor.php"] = 'reactor.stub';
        }

        return [
            $expectedFiles,
            $unexpectedFiles,
        ];
    }

    protected function assertDomainGenerated(
        string $domain,
        string $domainBaseRoot = 'Domain',
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

        [$expectedFiles, $unexpectedFiles] = $this->getExpectedFiles($domain, $domainBaseRoot, $createAggregateRoot, $createReactor);

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
            nameInput: $domain,
            domainBaseRoot: $domainBaseRoot,
            migration: '',
            createAggregateRoot: $createAggregateRoot,
            createReactor: $createReactor,
            indentation: $indentation,
            useUuid: $useUuid,
            domainName: $domain,
            domainId: Str::lcfirst(Str::camel($domain)),
            domainPath: '',
        );
        $settings->modelProperties->import($modelProperties);

        // Create stub replacer
        $stubReplacer = new StubReplacer($settings);

        // Assert content of files that must have been generated
        foreach ($expectedFiles as $generatedFile => $stubFile) {
            // Resolve stub
            [$stubFileResolved] = (new StubResolver('stubs/'.$stubFile, ''))
                ->resolve(app(), '', '');

            // Load stub
            $stub = File::get($stubFileResolved);

            // Replace content
            $stubReplacer
                ->replaceWithClosure($stub, 'class', fn () => $domain)
                ->replace($stub);

            // Load generated file
            $generated = File::get(app_path($generatedFile));

            // Assert namespace
            $this->assertStringContainsString('namespace App\\'.$stubReplacer->settings->domainBaseRoot.'\\'.$stubReplacer->settings->domainName, $generated);

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
            } elseif ($stubFile === 'projector.stub') {
                if ($useUuid) {
                    $this->assertMatchesRegularExpression("/'uuid' => \\\$event->{$stubReplacer->settings->domainId}Uuid/", $generated);
                } else {
                    $this->assertDoesNotMatchRegularExpression("/'id' => \\\$event->{$stubReplacer->settings->domainId}Id/", $generated);
                }
            }
        }

        // Assert files that must not exist
        foreach (array_keys($unexpectedFiles) as $generatedFile) {
            $this->assertFalse(File::exists(app_path($generatedFile)));
        }
    }
}
