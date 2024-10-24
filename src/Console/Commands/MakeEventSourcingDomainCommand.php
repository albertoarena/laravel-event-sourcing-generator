<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Commands\CommandSettings;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\Models\StubCallback;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubReplacer;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\Stubs;
use Albertoarena\LaravelEventSourcingGenerator\Helpers\ParseMigration;
use Exception;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MakeEventSourcingDomainCommand extends GeneratorCommand
{
    use HasBlueprintColumnType;

    protected CommandSettings $settings;

    protected StubReplacer $stubReplacer;

    /**
     * @var string
     */
    protected $signature = 'make:event-sourcing-domain 
                            {name : Name of domain}
                            {--d|domain=Domain : Domain base root}
                            {--m|migration= : Existing migration for the model, with or without timestamp prefix}
                            {--aggregate_root= : Create aggregate root}
                            {--r|reactor= : Create reactor}
                            {--indentation=4 : Indentation spaces}';

    /**
     * @var string
     */
    protected $description = 'Create a new domain for Spatie event sourcing';

    /**
     * @var string
     */
    protected $type = 'Domain';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Which domain you want to create?',
        ];
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../../../'.$stub;
    }

    protected function getDatabaseMigrations(): array
    {
        return Arr::map(
            File::files($this->laravel->basePath('database/migrations')),
            fn (SplFileInfo $path) => $path->getRelativePathname()
        );
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/event-domain.stub');
    }

    protected function qualifyDomain(string $name): string
    {
        $name = ltrim($name, '\\/');

        return str_replace('/', '\\', $name);
    }

    protected function getDomainPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.$this->settings->domainBaseRoot.'/'.str_replace('\\', '/', $name).'/';
    }

    protected function alreadyExistsDomain($rawName): bool
    {
        return $this->files->exists($this->getDomainPath($rawName));
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return "$rootNamespace\\{$this->settings->domainBaseRoot}";
    }

    /**
     * @throws FileNotFoundException
     */
    protected function stubToClass(
        string $stubPath,
        string $outputPath
    ): void {
        $stub = $this->files->get($stubPath);

        $this->stubReplacer->replace($stub);

        $content = $this->replaceNamespace($stub, $this->settings->domainName)
            ->replaceClass($stub, $this->settings->domainName);

        $this->files->put($outputPath, $content);
    }

    /**
     * @throws Exception
     */
    protected function loadProperties(): void
    {
        // Determine which is the model primary key and set properties
        if ($this->settings->migration) {
            try {
                // Load migration
                $migration = (new ParseMigration($this->settings->migration));
                foreach ($migration->properties() as $property) {
                    $this->settings->modelProperties->add($property);
                    if ($property->type === 'Carbon') {
                        $this->settings->useCarbon = true;
                    }
                }

                $this->settings->useUuid = $migration->primary() === 'uuid';
            } catch (Exception) {
                throw new Exception('Migration file does not exist');
            }
        } else {
            if ($this->confirm('Do you want to specify model properties?')) {
                while (true) {
                    $name = $this->ask('Property name? (exit to quit)');
                    if ($name === 'exit') {
                        break;
                    }
                    $type = $this->ask('Property type (e.g. string, int, boolean)?');
                    $this->settings->modelProperties->add(new MigrationCreateProperty(
                        name: $name,
                        type: $type,
                    ));
                }
            }

            $this->settings->useUuid = $this->confirm('Do you want to use uuid as model primary key?', true);
        }
    }

    protected function checkSpatieEventSourcing(): bool
    {
        return class_exists('Spatie\EventSourcing\EventSourcingServiceProvider');
    }

    /**
     * @throws Exception
     */
    protected function bootstrap(): bool
    {
        $this->settings = new CommandSettings(
            nameInput: Str::ucfirst($this->getNameInput()),
            domainBaseRoot: Str::ucfirst($this->option('domain')),
            migration: $this->option('migration'),
            createAggregateRoot: ! is_null($this->option('aggregate_root')) ? (bool) $this->option('aggregate_root') : null,
            createReactor: ! is_null($this->option('reactor')) ? (bool) $this->option('reactor') : null,
            indentation: (int) $this->option('indentation'),
            useUuid: false
        );

        $this->stubReplacer = new StubReplacer($this->settings);

        // Check if Spatie event-sourcing package has been installed
        if (! $this->checkSpatieEventSourcing()) {
            $this->components->error('Spatie Event Sourcing package has not been installed. Run what follows:');
            $this->components->error('composer require spatie/laravel-event-sourcing');

            return false;
        }

        // Check if the given name is a reserved word within PHP language
        if ($this->isReservedName($this->settings->nameInput)) {
            $this->components->error('The name "'.$this->settings->nameInput.'" is reserved by PHP.');

            return false;
        }

        // Check if the domain already exists
        if ($this->alreadyExistsDomain($this->settings->nameInput)) {
            $this->components->error($this->type.' already exists.');

            return false;
        }

        // Get other options
        if (is_null($this->settings->migration)) {
            if ($this->confirm('Do you want to import properties from existing database migration?', true)) {
                $this->settings->migration = $this->choice('Select database migration', $this->getDatabaseMigrations());
            }
        }

        // Load properties, from migration file or manually
        $this->loadProperties();

        // If not using uuid as primary key, no Aggregate Root
        if (! $this->settings->useUuid) {
            $this->line('You are not using uuid as model primary key, therefore you cannot use an AggregateRoot class');
            $this->settings->createAggregateRoot = false;
        }

        if (is_null($this->settings->createAggregateRoot)) {
            $this->settings->createAggregateRoot = $this->confirm('Do you want to create an AggregateRoot class?', true);
        }

        if (is_null($this->settings->createReactor)) {
            $this->settings->createReactor = $this->confirm('Do you want to create a Reactor class?', true);
        }

        $this->settings->domainName = $this->qualifyDomain($this->settings->nameInput);
        $this->settings->domainId = Str::lcfirst(Str::camel($this->settings->nameInput));
        $this->settings->domainPath = $this->getDomainPath($this->settings->domainName);

        return true;
    }

    protected function confirmChoices(): bool
    {
        $modelProperties = $this->settings->modelProperties->withoutReservedFields()->toArray();

        $this->line('Your choices:');
        $this->table(
            ['Option', 'Choice'],
            [
                ['Domain', $this->settings->nameInput],
                ['Root domain folder', $this->settings->domainBaseRoot],
                ['Use migration', basename($this->settings->migration) ?: 'no'],
                ['Primary key', $this->settings->primaryKey()],
                ['Create AggregateRoot class', $this->settings->createAggregateRoot ? 'yes' : 'no'],
                ['Create Reactor class', $this->settings->createReactor ? 'yes' : 'no'],
                [
                    'Model properties',
                    $modelProperties ?
                        implode(',', Arr::map(
                            $modelProperties,
                            function (MigrationCreateProperty $property) {
                                $type = $this->columnTypeToBuiltInType($property->type);
                                if (Str::startsWith($type, '?')) {
                                    $nullable = '?';
                                    $type = Str::substr($type, 1);
                                } else {
                                    $nullable = $property->nullable ? '?' : '';
                                }

                                return "$nullable$type $property->name";
                            })
                        ) :
                        'none',
                ],
            ]
        );

        return $this->confirm('Do you confirm the generation of the domain?', true);
    }

    protected function createDirectories(): self
    {
        // Create domain directories
        $this->makeDirectory($this->settings->domainPath.'/Actions/*');
        $this->makeDirectory($this->settings->domainPath.'/DataTransferObjects/*');
        $this->makeDirectory($this->settings->domainPath.'/Events/*');
        $this->makeDirectory($this->settings->domainPath.'/Projections/*');
        $this->makeDirectory($this->settings->domainPath.'/Projectors/*');
        $this->makeDirectory($this->settings->domainPath.'/Reactors/*');

        return $this;
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createDomainFiles(): self
    {
        $stubCallback = new StubCallback(
            function (string $stubPath, string $outputPath) {
                $this->stubToClass($stubPath, $outputPath);
            },
        );

        (new Stubs(
            laravel: $this->laravel,
            domainPath: $this->settings->domainPath,
            domainName: $this->settings->domainName,
            createAggregateRoot: (bool) $this->settings->createAggregateRoot,
            createReactor: (bool) $this->settings->createReactor,
        ))->resolve($stubCallback);

        return $this;
    }

    protected function outputResult(): void
    {
        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $this->settings->domainName));
    }

    // @phpstan-ignore method.childReturnType
    public function handle(): int
    {
        try {
            if (! $this->bootstrap()) {
                return self::FAILURE;
            }

            if (! $this->confirmChoices()) {
                $this->components->warn('Aborted');

                return self::FAILURE;
            }

            $this->createDirectories()
                ->createDomainFiles()
                ->outputResult();

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->components->error('There was an error: '.$e->getMessage().'.');

            return self::FAILURE;
        }
    }
}
