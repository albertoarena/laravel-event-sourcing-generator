<?php

namespace Tests\Concerns;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts\BlueprintUnsupportedInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Contracts\AcceptedNotificationInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Contracts\DefaultSettingsInterface;
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
            $expectedFiles["tests/Unit/$namespace/$domain/{$model}Test.php"] = 'test.stub';
        } else {
            $unexpectedFiles["tests/Unit/$namespace/$domain/{$model}Test.php"] = 'test.stub';
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

    protected function getReactorMatches(string $namespace, string $domain, string $model, bool $createFailedEvents): array
    {
        return [
            "use App\\$namespace\\$domain\\Events\\{$model}Created" => true,
            "use App\\$namespace\\$domain\\Events\\{$model}Deleted" => true,
            "use App\\$namespace\\$domain\\Events\\{$model}Updated" => true,
            "use App\\$namespace\\$domain\\Events\\{$model}CreationFailed" => $createFailedEvents,
            "use App\\$namespace\\$domain\\Events\\{$model}DeletionFailed" => $createFailedEvents,
            "use App\\$namespace\\$domain\\Events\\{$model}UpdateFailed" => $createFailedEvents,
            "public function on{$model}Created({$model}Created \$event)" => true,
            "public function on{$model}Deleted({$model}Deleted \$event)" => true,
            "public function on{$model}Updated({$model}Updated \$event)" => true,
            "public function on{$model}CreationFailed({$model}CreationFailed \$event)" => $createFailedEvents,
            "public function on{$model}DeletionFailed({$model}DeletionFailed \$event)" => $createFailedEvents,
            "public function on{$model}UpdateFailed({$model}UpdateFailed \$event)" => $createFailedEvents,
        ];
    }

    protected function assertAggregateRoot(string $generated, CommandSettings $settings): void
    {
        if ($settings->useUuid) {
            $this->assertMatchesRegularExpression("/{$settings->nameAsPrefix}Uuid: \\\$this->uuid\(\)/", $generated);
        } else {
            $this->assertMatchesRegularExpression("/{$settings->nameAsPrefix}Id: \\\$this->id\(\)/", $generated);
        }
    }

    protected function assertDataTransferObject(string $generated, CommandSettings $settings): void
    {
        if (! count($settings->modelProperties->toArray())) {
            $this->assertMatchesRegularExpression("/\/\/ Add here public properties, e.g.:/", $generated);
        } else {
            // Assert that model properties are using camel format
            foreach ($settings->modelProperties->toArray() as $property) {
                $name = Str::camel($property->name);

                $type = $property->type->toNormalisedBuiltInType();
                $nullable = $property->type->nullable ? '\\?' : '';

                $this->assertMatchesRegularExpression("/public $nullable$type \\$$name/", $generated);
            }
            foreach ($settings->ignoredProperties->toArray() as $property) {
                $name = Str::camel($property->name);

                $this->assertMatchesRegularExpression("/\/\/ @todo public {$property->type->type} \\$$name, column type is not yet supported/", $generated);
            }

            foreach (BlueprintUnsupportedInterface::SKIPPED_METHODS as $method) {
                $this->assertDoesNotMatchRegularExpression("/public .* \\\$$method;/", $generated);
            }
            if ($settings->useCarbon) {
                $this->assertStringContainsString('use Illuminate\Support\Carbon;', $generated);
            } else {
                $this->assertStringNotContainsString('use Illuminate\Support\Carbon;', $generated);
            }
        }
    }

    protected function assertProjection(string $generated, CommandSettings $settings): void
    {
        if ($settings->useUuid) {
            $this->assertMatchesRegularExpression("/'uuid' => 'string',/", $generated);
        } else {
            $this->assertMatchesRegularExpression("/'id' => 'int',/", $generated);
        }
        foreach ($settings->modelProperties->toArray() as $property) {
            $type = $property->type->toProjection();
            $this->assertMatchesRegularExpression("/\s*'$property->name',\n/", $generated);
            $this->assertMatchesRegularExpression("/'$property->name' => '$type'/", $generated);
        }
        foreach ($settings->ignoredProperties->toArray() as $property) {
            $this->assertMatchesRegularExpression("/\/\/ @todo '$property->name', column type '{$property->type->type}' is not yet supported/", $generated);
            $this->assertMatchesRegularExpression("/\/\/ @todo '$property->name' => '{$property->type->type}', column type is not yet supported/", $generated);
        }
        foreach (BlueprintUnsupportedInterface::SKIPPED_METHODS as $method) {
            $this->assertDoesNotMatchRegularExpression("/'$method' => '.*'/", $generated);
        }
        if ($settings->useCarbon) {
            $this->assertStringContainsString('use Illuminate\Support\Carbon;', $generated);
        } else {
            $this->assertStringNotContainsString('use Illuminate\Support\Carbon;', $generated);
        }
    }

    protected function assertProjector(string $generated, CommandSettings $settings): void
    {
        if ($settings->useUuid) {
            $this->assertMatchesRegularExpression("/'uuid' => \\\$event->{$settings->nameAsPrefix}Uuid/", $generated);
        } else {
            $this->assertDoesNotMatchRegularExpression("/'id' => \\\$event->{$settings->nameAsPrefix}Id/", $generated);
        }
        // Assert that model properties are using camel format
        foreach ($settings->modelProperties->toArray() as $property) {
            $name = Str::camel($property->name);
            $this->assertMatchesRegularExpression("/'$property->name'\s=>\s\\\$event->{$settings->nameAsPrefix}Data->$name/", $generated);
        }
        foreach ($settings->ignoredProperties->toArray() as $property) {
            $name = Str::camel($property->name);
            $this->assertMatchesRegularExpression("/\/\/ @todo '$property->name'\s=>\s\\\$event->{$settings->nameAsPrefix}Data->$name, column type '{$property->type->type}' is not yet supported/", $generated);
        }
        foreach (BlueprintUnsupportedInterface::SKIPPED_METHODS as $method) {
            $this->assertDoesNotMatchRegularExpression("/'$method'\s=>\s\\\$event->{$settings->nameAsPrefix}Data->$method/", $generated);
        }

        // Assert failed events
        foreach ($this->getProjectorFailedEventMatches($settings->namespace, $settings->domain, $settings->model) as $match) {
            if ($settings->createFailedEvents) {
                $this->assertStringContainsString($match, $generated);
            } else {
                $this->assertStringNotContainsString($match, $generated);
            }
        }

        // Assert notifications
        foreach ($this->getProjectorNotificationMatches($settings->namespace, $settings->domain, $settings->model) as $match) {
            if ($settings->notifications) {
                $this->assertStringContainsString($match, $generated);
            } else {
                $this->assertStringNotContainsString($match, $generated);
            }
        }
    }

    protected function assertReactor(string $generated, CommandSettings $settings): void
    {
        foreach ($this->getReactorMatches($settings->namespace, $settings->domain, $settings->model, $settings->createFailedEvents) as $match => $expected) {
            if ($expected) {
                $this->assertStringContainsString($match, $generated);
            } else {
                $this->assertStringNotContainsString($match, $generated);
            }
        }
    }

    protected function assertTest(string $generated, CommandSettings $settings): void
    {
        if ($settings->createUnitTest) {
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

            if ($settings->notifications) {
                $this->assertStringContainsString('Notification::fake()', $generated);
                $this->assertStringContainsString('Notification::assertSentTo', $generated);
                $this->assertStringContainsString($settings->model.'CreatedNotification::class', $generated);
                $this->assertStringContainsString($settings->model.'UpdatedNotification::class', $generated);
                $this->assertStringContainsString($settings->model.'DeletedNotification::class', $generated);
                if ($settings->createFailedEvents) {
                    $this->assertStringContainsString($settings->model.'CreationFailedNotification::class', $generated);
                    $this->assertStringContainsString($settings->model.'UpdateFailedNotification::class', $generated);
                    $this->assertStringContainsString($settings->model.'DeletionFailedNotification::class', $generated);
                } else {
                    $this->assertStringNotContainsString($settings->model.'CreationFailedNotification::class', $generated);
                    $this->assertStringNotContainsString($settings->model.'UpdateFailedNotification::class', $generated);
                    $this->assertStringNotContainsString($settings->model.'DeletionFailedNotification::class', $generated);
                }
            }
        }
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
        array $ignoredProperties = [],
        int $indentation = 4,
        bool $createUnitTest = false,
        bool $createFailedEvents = false,
        array $notifications = [],
        string $rootFolder = DefaultSettingsInterface::APP,
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
                $this->assertTrue(File::exists(base_path($rootFolder.'/'.$generatedFile)));
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
            rootFolder: $rootFolder,
            useUuid: $useUuid,
            nameAsPrefix: Str::lcfirst(Str::camel($model)),
            domainPath: '',
            createUnitTest: $createUnitTest,
            createFailedEvents: $createFailedEvents,
            modelProperties: $modelProperties,
            ignoredProperties: $ignoredProperties,
        );

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
                base_path($settings->rootFolder.'/'.$generatedFile)
            );

            // Assert namespace
            $baseNamespace = $isTest ? 'Tests' : 'App';
            $this->assertStringContainsString('namespace '.$baseNamespace.'\\'.$settings->namespace.'\\'.$settings->domain, $generated);

            // Assert if-blocks have been removed
            $this->assertStringNotContainsString('{%', $generated);

            // Assert specific expectations
            switch ($stubFile) {
                case 'aggregate-root.stub':
                    $this->assertAggregateRoot($generated, $settings);
                    break;
                case 'data-transfer-object.stub':
                    $this->assertDataTransferObject($generated, $settings);
                    break;
                case 'projection.stub':
                    $this->assertProjection($generated, $settings);
                    break;
                case 'projector.stub':
                    $this->assertProjector($generated, $settings);
                    break;
                case 'reactor.stub':
                    $this->assertReactor($generated, $settings);
                    break;
                case 'test.stub':
                    $this->assertTest($generated, $settings);
                    break;
                default:
                    break;
            }
        }

        // Assert files that must not exist
        foreach (array_keys($unexpectedFiles) as $generatedFile) {
            $this->assertFalse(File::exists(app_path($generatedFile)));
        }
    }
}
