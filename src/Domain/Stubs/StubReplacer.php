<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Commands\CommandSettings;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StubReplacer
{
    use HasBlueprintColumnType;

    /** @var MigrationCreateProperty[] */
    protected array $modelProperties;

    public function __construct(
        public CommandSettings &$settings,
    ) {
        $this->modelProperties = [];
    }

    protected function getModelProperties(): array
    {
        if (! $this->modelProperties) {
            /** @var MigrationCreateProperty $property */
            foreach ($this->settings->modelProperties->withoutReservedFields()->toArray() as $property) {
                $this->modelProperties[$property->name] = $property;
            }
        }
        //        dd($this->settings->modelProperties->withoutReservedFields());

        return $this->modelProperties;
    }

    protected function getSearch(...$patterns): array
    {
        $ret = [
            [],
            [],
            [],
        ];
        foreach ($patterns as $pattern) {
            $ret[0][] = 'Dummy'.Str::ucfirst(Str::camel($pattern));
            $ret[1][] = '{{ '.Str::lower(Str::kebab($pattern)).' }}';
            $ret[2][] = '{{'.Str::lower(Str::kebab($pattern)).'}}';
        }

        return $ret;
    }

    protected function getIndentSpace(int $tabs): string
    {
        // Use always default indentation.
        return Str::repeat('    ', $tabs);
    }

    protected function replaceDomain(&$stub): self
    {
        $this->replaceWithClosure($stub, 'domain', fn () => $this->settings->domainBaseRoot);
        $this->replaceWithClosure($stub, 'id', fn () => $this->settings->domainId);

        return $this;
    }

    protected function replaceConstructorProperties(&$stub): self
    {
        $indentSpace2 = $this->getIndentSpace(2);

        $preparedProperties = Arr::map(
            $this->getModelProperties(),
            function (MigrationCreateProperty $property) {
                $type = $this->columnTypeToBuiltInType($property->type);
                $type = $this->normaliseCarbon($type);
                $nullable = $property->nullable ? '?' : '';

                return "public $nullable$type \$$property->name";
            }
        );
        $preparedProperties = implode(",\n$indentSpace2", $preparedProperties);
        $this->replaceWithClosure($stub, 'properties:data-transfer-object:constructor', fn () => $preparedProperties);

        // Normalise empty constructor if no properties
        if (! $preparedProperties) {
            $stub = preg_replace(
                '/public function __construct\(\n\s*\) {}/',
                "public function __construct(\n$indentSpace2// Add here public properties, e.g.:\n$indentSpace2// public string \$name\n{$this->settings->indentSpace}) {}",
                $stub);
        }

        return $this;
    }

    protected function replaceProjectionFillableProperties(&$stub): self
    {
        $indentSpace2 = $this->getIndentSpace(2);

        // Inject primary key
        $properties = $this->getModelProperties();
        array_unshift($properties, new MigrationCreateProperty($this->settings->primaryKey(), 'string'));

        $preparedProperties = Arr::map($properties, fn (MigrationCreateProperty $property) => "'$property->name',");
        $preparedProperties = implode("\n$indentSpace2", $preparedProperties);

        $this->replaceWithClosure($stub, 'properties:projection:fillable', fn () => $preparedProperties);

        return $this;
    }

    protected function replaceProjectionCastProperties(&$stub): self
    {
        $indentSpace2 = $this->getIndentSpace(2);

        $preparedProperties = array_merge(
            [$this->settings->useUuid ? "'uuid' => 'string'," : "'id' => 'int',"],
            Arr::map(
                $this->getModelProperties(),
                function (MigrationCreateProperty $property) {
                    $type = $this->columnTypeToBuiltInType($property->type);
                    $type = $this->carbonToBuiltInType($type);

                    return "'$property->name' => '$type',";
                })
        );
        $preparedProperties = implode("\n$indentSpace2", $preparedProperties);

        $this->replaceWithClosure($stub, 'properties:projection:cast', fn () => $preparedProperties);

        return $this;
    }

    protected function replaceProjectionPhpDocProperties(&$stub): self
    {
        $preparedProperties = Arr::map(
            $this->getModelProperties(),
            function (MigrationCreateProperty $property) {
                $type = $this->columnTypeToBuiltInType($property->type);
                $type = $this->normaliseCarbon($type);
                $nullable = $property->nullable ? '|null' : '';

                return " * @property $type$nullable \$$property->name";
            }
        );
        $preparedProperties = implode("\n", $preparedProperties);

        $this->replaceWithClosure($stub, 'properties:projection:phpdoc', fn () => $preparedProperties);

        return $this;
    }

    protected function replaceProjectorProperties(&$stub): self
    {
        $indentSpace4 = $this->getIndentSpace(4);

        $domainId = $this->settings->domainId;

        $preparedProperties = Arr::map(
            $this->getModelProperties(),
            fn (MigrationCreateProperty $property) => "'$property->name' => \$event->{$domainId}Data->$property->name,"
        );

        foreach ($this->getSearch('properties:projector') as $search) {
            $stub = str_replace(
                $search,
                [implode("\n$indentSpace4", $preparedProperties)],
                $stub
            );
        }

        return $this;
    }

    protected function replacePrimaryKey(&$stub): self
    {
        $primaryKey = $this->settings->primaryKey();
        $this->replaceWithClosure($stub, 'primary_key', fn () => $primaryKey);

        $primaryKeyType = $this->settings->useUuid ? 'string' : 'int';
        $this->replaceWithClosure($stub, 'primary_key:type', fn () => $primaryKeyType);

        $primaryKeyUppercase = Str::ucfirst($primaryKey);
        $this->replaceWithClosure($stub, 'primary_key:uppercase', fn () => $primaryKeyUppercase);

        return $this;
    }

    protected function replaceIfBlocks(&$stub): self
    {
        $ifBlockConditions = [
            '{% if uuid %}' => ! $this->settings->useUuid,
            '{% if !uuid %}' => $this->settings->useUuid,
            '{% if useCarbon %}' => ! $this->settings->useCarbon,
            '{% endif %}' => false,
        ];

        $stub2 = explode("\n", $stub);
        $isRemoving = false;
        foreach ($stub2 as $index => $line) {
            $removeThis = false;
            $line = trim($line);

            if (isset($ifBlockConditions[$line])) {
                $isRemoving = $ifBlockConditions[$line];
                $removeThis = true;
            }
            if ($isRemoving || $removeThis) {
                unset($stub2[$index]);
            }
        }

        $stub = implode("\n", $stub2);

        // Fix any remaining block
        $stub = Str::replaceMatches(
            Arr::map(array_keys($ifBlockConditions), fn ($condition) => "/\s*\$condition/"),
            ["\n", "\n", "\n", ''],
            $stub
        );

        return $this;
    }

    protected function replaceIndentation(&$stub): self
    {
        $defaultIndentation = Str::repeat(' ', 4);
        $currentIndentation = $this->settings->indentSpace;
        if ($defaultIndentation !== $currentIndentation) {
            $stub = Str::replace($defaultIndentation, $currentIndentation, $stub);
        }

        return $this;
    }

    public function replace(&$stub): self
    {
        return $this->replaceDomain($stub)
            ->replaceConstructorProperties($stub)
            ->replaceProjectionFillableProperties($stub)
            ->replaceProjectionCastProperties($stub)
            ->replaceProjectionPhpDocProperties($stub)
            ->replaceProjectorProperties($stub)
            ->replacePrimaryKey($stub)
            ->replaceIfBlocks($stub)
            ->replaceIndentation($stub);
    }

    public function replaceWithClosure(&$stub, string $searchPattern, Closure $closure): self
    {
        foreach ($this->getSearch($searchPattern) as $search) {
            $stub = str_replace(
                $search,
                [$closure($search, $stub)],
                $stub
            );
        }

        return $this;
    }
}
