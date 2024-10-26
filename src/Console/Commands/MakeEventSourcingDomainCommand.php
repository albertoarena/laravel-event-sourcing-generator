<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models\CommandSettings;
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
                            {model : The name of the model}
                            {--d|domain= : The name of the domain}
                            {--namespace=Domain : The namespace or root folder}
                            {--m|migration= : Indicate any existing migration for the model, with or without timestamp prefix}
                            {--a|aggregate_root= : Indicate if aggregate root must be created or not (accepts 0 or 1)}
                            {--r|reactor= : Indicate if reactor must be created or not (accepts 0 or 1)}
                            {--i|indentation=4 : Indentation spaces}';

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
            'model' => 'Which is the name of the model?',
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

        return $this->laravel['path'].'/'.$this->settings->namespace.'/'.str_replace('\\', '/', $name).'/';
    }

    protected function alreadyExistsModel(): bool
    {
        return $this->files->exists($this->getDomainPath($this->settings->model).'Actions/Create'.$this->settings->model.'.php');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return "$rootNamespace\\{$this->settings->namespace}";
    }

    /**
     * @throws FileNotFoundException
     */
    protected function stubToClass(
        string $stubPath,
        string $outputPath
    ): void {
        if ($this->files->exists($outputPath)) {
            $basePath = $this->laravel->basePath('app/');
            $outputPath = Str::replaceFirst($basePath, '', $outputPath);
            $this->components->warn('A file already exists (it was not overwritten): '.$outputPath);

            return;
        }

        $stub = $this->files->get($stubPath);

        $this->stubReplacer->replace($stub);

        $content = $this->replaceNamespace($stub, $this->settings->domain)
            ->replaceClass($stub, $this->settings->model);

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
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            if ($this->confirm('Do you want to specify model properties?')) {
                while (true) {
                    $name = $this->ask('Property name? (exit to quit)');
                    if ($name === 'exit') {
                        break;
                    }
                    $type = $this->ask('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)');
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

    protected function getModelInput(): string
    {
        return Str::ucfirst(Str::trim($this->argument('model')));
    }

    protected function getDomainInput(): string
    {
        $domain = ! is_null($this->option('domain')) ? Str::ucfirst($this->option('domain')) : null;
        if (! $domain) {
            $domain = $this->ask('Which is the name of the domain?');
        }

        return Str::ucfirst($domain);
    }

    /**
     * @throws Exception
     */
    protected function bootstrap(): bool
    {
        $this->settings = new CommandSettings(
            model: $this->getModelInput(),
            domain: $this->getDomainInput(),
            namespace: Str::ucfirst($this->option('namespace')),
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
        if ($this->isReservedName($this->settings->model)) {
            $this->components->error('The model "'.$this->settings->model.'" is reserved by PHP.');

            return false;
        }

        // Check if the given domain is a reserved word within PHP language
        if ($this->isReservedName($this->settings->domain)) {
            $this->components->error('The domain "'.$this->settings->domain.'" is reserved by PHP.');

            return false;
        }

        // Check if the domain already exists
        if ($this->alreadyExistsModel()) {
            $this->components->error('Model already exists.');

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

        $this->settings->nameAsPrefix = Str::lcfirst(Str::camel($this->settings->model));
        $this->settings->domainPath = $this->getDomainPath($this->qualifyDomain($this->settings->domain));

        return true;
    }

    protected function confirmChoices(): bool
    {
        $modelProperties = $this->settings->modelProperties->withoutReservedFields()->toArray();

        $this->line('Your choices:');
        $this->table(
            ['Option', 'Choice'],
            [
                ['Model', $this->settings->model],
                ['Domain', $this->settings->domain],
                ['Namespace', $this->settings->namespace],
                ['Path', $this->settings->namespace.'/'.$this->settings->domain.'/'.$this->settings->model],
                ['Use migration', basename($this->settings->migration) ?: 'no'],
                ['Primary key', $this->settings->primaryKey()],
                ['Create AggregateRoot class', $this->settings->createAggregateRoot ? 'yes' : 'no'],
                ['Create Reactor class', $this->settings->createReactor ? 'yes' : 'no'],
                [
                    'Model properties',
                    $modelProperties ?
                        implode("\n", Arr::map(
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
        if ($this->settings->createReactor) {
            $this->makeDirectory($this->settings->domainPath.'/Reactors/*');
        }

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
            settings: $this->settings,
        ))->resolve($stubCallback);

        return $this;
    }

    protected function outputResult(): void
    {
        $this->components->info(sprintf('%s [%s] with model [%s] created successfully.', $this->type, $this->settings->domain, $this->settings->model));
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
