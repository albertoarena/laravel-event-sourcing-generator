<?php

namespace Tests\Concerns;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts\BlueprintUnsupportedInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Contracts\AcceptedNotificationInterface;
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
        bool $createUnitTest,
        bool $createFailedEvents,
        array $notifications,
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

        if ($createFailedEvents) {
            $expectedFiles["$namespace/$domain/Events/{$model}CreationFailed.php"] = 'events.creation_failed.stub';
            $expectedFiles["$namespace/$domain/Events/{$model}DeletionFailed.php"] = 'events.deletion_failed.stub';
            $expectedFiles["$namespace/$domain/Events/{$model}UpdateFailed.php"] = 'events.update_failed.stub';
        } else {
            $unexpectedFiles["$namespace/$domain/Events/{$model}CreationFailed.php"] = 'events.creation_failed.stub';
            $unexpectedFiles["$namespace/$domain/Events/{$model}DeletionFailed.php"] = 'events.deletion_failed.stub';
            $unexpectedFiles["$namespace/$domain/Events/{$model}UpdateFailed.php"] = 'events.update_failed.stub';
        }

        if ($notifications) {
            $expectedFiles["$namespace/$domain/Notifications/Concerns/HasDataAsArray.php"] = 'notifications.concerns.has_data_as_array.stub';
            $expectedFiles["$namespace/$domain/Notifications/{$model}Created.php"] = 'notifications.created.stub';
            $expectedFiles["$namespace/$domain/Notifications/{$model}Deleted.php"] = 'notifications.deleted.stub';
            $expectedFiles["$namespace/$domain/Notifications/{$model}Updated.php"] = 'notifications.updated.stub';
            if ($createFailedEvents) {
                $expectedFiles["$namespace/$domain/Notifications/{$model}CreationFailed.php"] = 'notifications.creation_failed.stub';
                $expectedFiles["$namespace/$domain/Notifications/{$model}DeletionFailed.php"] = 'notifications.deletion_failed.stub';
                $expectedFiles["$namespace/$domain/Notifications/{$model}UpdateFailed.php"] = 'notifications.update_failed.stub';
            } else {
                $unexpectedFiles["$namespace/$domain/Notifications/{$model}CreationFailed.php"] = 'notifications.creation_failed.stub';
                $unexpectedFiles["$namespace/$domain/Notifications/{$model}DeletionFailed.php"] = 'notifications.deletion_failed.stub';
                $unexpectedFiles["$namespace/$domain/Notifications/{$model}UpdateFailed.php"] = 'notifications.update_failed.stub';
            }

            if (in_array(AcceptedNotificationInterface::TEAMS, $notifications)) {
                $expectedFiles["$namespace/$domain/Notifications/Concerns/HasMicrosoftTeamsNotification.php"] = 'notifications.concerns.has_microsoft_teams_notification.stub';
            } elseif (in_array(AcceptedNotificationInterface::SLACK, $notifications)) {
                $expectedFiles["$namespace/$domain/Notifications/Concerns/HasSlackNotification.php"] = 'notifications.concerns.has_slack_notification.stub';
            } else {
                $unexpectedFiles["$namespace/$domain/Notifications/Concerns/HasMicrosoftTeamsNotification.php"] = 'notifications.concerns.has_microsoft_teams_notification.stub';
                $unexpectedFiles["$namespace/$domain/Notifications/Concerns/HasSlackNotification.php"] = 'notifications.concerns.has_slack_notification.stub';
            }
        } else {
            $unexpectedFiles["$namespace/$domain/Notifications/Concerns/HasDataAsArray.php"] = 'notifications.concerns.has_data_as_array.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/Concerns/HasMicrosoftTeamsNotification.php"] = 'notifications.concerns.has_microsoft_teams_notification.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/Concerns/HasSlackNotification.php"] = 'notifications.concerns.has_slack_notification.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/{$model}Created.php"] = 'notifications.created.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/{$model}Deleted.php"] = 'notifications.deleted.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/{$model}Updated.php"] = 'notifications.updated.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/{$model}CreationFailed.php"] = 'notifications.creation_failed.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/{$model}DeletionFailed.php"] = 'notifications.deletion_failed.stub';
            $unexpectedFiles["$namespace/$domain/Notifications/{$model}UpdateFailed.php"] = 'notifications.update_failed.stub';
        }

        return [
            $expectedFiles,
            $unexpectedFiles,
        ];
    }

    protected function getProjectorFailedEventMatches(string $namespace, string $domain, string $model): array
    {
        return [
            "use App\\$namespace\\$domain\\Events\\{$model}CreationFailed",
            "use App\\$namespace\\$domain\\Events\\{$model}DeletionFailed",
            "use App\\$namespace\\$domain\\Events\\{$model}UpdateFailed",
            "event(new {$model}CreationFailed(",
            "event(new {$model}DeletionFailed(",
            "event(new {$model}UpdateFailed(",
            "public function on{$model}CreationFailed({$model}CreationFailed \$event)",
            "public function on{$model}DeletionFailed({$model}DeletionFailed \$event)",
            "public function on{$model}UpdateFailed({$model}UpdateFailed \$event)",
        ];
    }

    protected function getProjectorNotificationMatches(string $namespace, string $domain, string $model): array
    {
        return [
            "use App\\$namespace\\$domain\\Notifications\\{$model}Created",
            "use App\\$namespace\\$domain\\Notifications\\{$model}CreationFailed",
            "use App\\$namespace\\$domain\\Notifications\\{$model}Deleted",
            "use App\\$namespace\\$domain\\Notifications\\{$model}DeletionFailed",
            "use App\\$namespace\\$domain\\Notifications\\{$model}UpdateFailed",
            "use App\\$namespace\\$domain\\Notifications\\{$model}Updated",
            "Notification::send(new AnonymousNotifiable, new {$model}CreatedNotification(",
            "Notification::send(new AnonymousNotifiable, new {$model}CreationFailedNotification(",
            "Notification::send(new AnonymousNotifiable, new {$model}DeletedNotification(",
            "Notification::send(new AnonymousNotifiable, new {$model}DeletionFailedNotification(",
            "Notification::send(new AnonymousNotifiable, new {$model}UpdateFailedNotification(",
            "Notification::send(new AnonymousNotifiable, new {$model}UpdatedNotification(",
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
        bool $createFailedEvents = false,
        array $notifications = [],
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
            createFailedEvents: $createFailedEvents,
            notifications: $notifications,
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
            notifications: $notifications,
            useUuid: $useUuid,
            nameAsPrefix: Str::lcfirst(Str::camel($model)),
            domainPath: '',
            createUnitTest: $createUnitTest,
            createFailedEvents: $createFailedEvents,
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
            $domain = $settings->domain;
            $this->assertStringContainsString('namespace '.$baseNamespace.'\\'.$settings->namespace.'\\'.$settings->domain, $generated);

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
                    foreach (BlueprintUnsupportedInterface::SKIPPED_METHODS as $method) {
                        $this->assertDoesNotMatchRegularExpression("/public .* \\\$$method;/", $generated);
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
                foreach (BlueprintUnsupportedInterface::SKIPPED_METHODS as $method) {
                    $this->assertDoesNotMatchRegularExpression("/'$method' => '.*'/", $generated);
                }
            } elseif ($stubFile === 'projector.stub') {
                if ($useUuid) {
                    $this->assertMatchesRegularExpression("/'uuid' => \\\$event->{$settings->nameAsPrefix}Uuid/", $generated);
                } else {
                    $this->assertDoesNotMatchRegularExpression("/'id' => \\\$event->{$settings->nameAsPrefix}Id/", $generated);
                }
                // Assert that model properties are using camel format
                foreach ($settings->modelProperties->toArray() as $item) {
                    $name = Str::camel($item->name);
                    $this->assertMatchesRegularExpression("/'$item->name'\s=>\s\\\$event->{$settings->nameAsPrefix}Data->$name/", $generated);
                }
                foreach (BlueprintUnsupportedInterface::SKIPPED_METHODS as $method) {
                    $this->assertDoesNotMatchRegularExpression("/'$method'\s=>\s\\\$event->{$settings->nameAsPrefix}Data->$method/", $generated);
                }

                // Assert failed events
                foreach ($this->getProjectorFailedEventMatches($namespace, $domain, $model) as $match) {
                    if ($createFailedEvents) {
                        $this->assertStringContainsString($match, $generated);
                    } else {
                        $this->assertStringNotContainsString($match, $generated);
                    }
                }

                // Assert notifications
                foreach ($this->getProjectorNotificationMatches($namespace, $domain, $model) as $match) {
                    if ($notifications) {
                        $this->assertStringContainsString($match, $generated);
                    } else {
                        $this->assertStringNotContainsString($match, $generated);
                    }
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

                    if ($notifications) {
                        $this->assertStringContainsString('Notification::fake()', $generated);
                        $this->assertStringContainsString('Notification::assertSentTo', $generated);
                        $this->assertStringContainsString($model.'CreatedNotification::class', $generated);
                        $this->assertStringContainsString($model.'UpdatedNotification::class', $generated);
                        $this->assertStringContainsString($model.'DeletedNotification::class', $generated);
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
