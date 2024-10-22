<?php

namespace Albertoarena\LaravelDomainGenerator\Domain\Stubs;

use Albertoarena\LaravelDomainGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelDomainGenerator\Models\CommandSettings;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StubReplacer
{
    use HasBlueprintColumnType;

    public function __construct(
        public CommandSettings $settings,
    ) {}

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
        return Str::repeat($this->settings->indentSpace, $tabs);
    }

    protected function replaceDomain(&$stub): self
    {
        $this->replaceWithClosure($stub, 'domain', fn () => $this->settings->domainBaseRoot);
        $this->replaceWithClosure($stub, 'id', fn () => $this->settings->domainId);

        return $this;
    }

    protected function replaceConstructorArguments(&$stub): self
    {
        $indentSpace2 = $this->getIndentSpace(2);

        $preparedArguments = Arr::map(
            Arr::except($this->settings->modelProperties, ['timestamps']),
            function ($type, $name) {
                $type = $this->columnTypeToType($type);

                return "public $type \$$name";
            }
        );

        foreach ($this->getSearch('arguments:constructor') as $search) {
            $stub = str_replace(
                $search,
                [implode(",\n$indentSpace2", $preparedArguments)],
                $stub
            );
        }

        // Normalise empty constructor if no arguments
        if (! $preparedArguments) {
            $stub = preg_replace(
                '/public function __construct\(\n\s*\) {}/',
                "public function __construct(\n$indentSpace2// Add here public properties, e.g.:\n$indentSpace2// public string \$name\n{$this->settings->indentSpace}) {}",
                $stub);
        }

        return $this;
    }

    protected function replaceModelFillableArguments(&$stub): self
    {
        $indentSpace2 = $this->getIndentSpace(2);

        $preparedArguments = Arr::map(
            array_merge(
                [
                    $this->settings->primaryKey(),
                ],
                array_keys(Arr::except($this->settings->modelProperties, ['timestamps']))
            ),
            fn ($property) => "'$property',"
        );

        foreach ($this->getSearch('arguments:model:fillable') as $search) {
            $stub = str_replace(
                $search,
                [implode("\n$indentSpace2", $preparedArguments)],
                $stub
            );
        }

        return $this;
    }

    protected function replaceModelCastArguments(&$stub): self
    {
        $indentSpace2 = $this->getIndentSpace(2);

        $preparedArguments = array_merge(
            [$this->settings->useUuid ? "'uuid' => 'string'," : "'id' => 'int',"],
            Arr::map(
                Arr::except($this->settings->modelProperties, ['timestamps', 'uuid']),
                function ($type, $name) {
                    if ($type === 'Carbon') {
                        $type = 'date:Y-m-d H:i:s';
                    }

                    return "'$name' => '$type',";
                })
        );

        foreach ($this->getSearch('arguments:model:cast') as $search) {
            $stub = str_replace(
                $search,
                [implode("\n$indentSpace2", $preparedArguments)],
                $stub
            );
        }

        return $this;
    }

    protected function replaceProjectionArguments(&$stub): self
    {
        $preparedArguments = Arr::map(
            Arr::except($this->settings->modelProperties, ['timestamps', 'uuid']),
            function ($type, $name) {
                $type = $this->columnTypeToType($type);

                return " * @property $type \$$name";
            }
        );
        $preparedArguments = implode("\n", $preparedArguments);

        $this->replaceWithClosure($stub, 'arguments:projection', fn () => $preparedArguments);

        return $this;
    }

    protected function replaceProjectorArguments(&$stub): self
    {
        $indentSpace4 = $this->getIndentSpace(4);

        $domainId = $this->settings->domainId;

        $preparedArguments = Arr::map(
            Arr::except($this->settings->modelProperties, ['timestamps', 'uuid']),
            fn ($type, $name) => "'$name' => \$event->{$domainId}Data->$name,"
        );

        foreach ($this->getSearch('arguments:projector') as $search) {
            $stub = str_replace(
                $search,
                [implode("\n$indentSpace4", $preparedArguments)],
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
        $stub2 = explode("\n", $stub);
        $removing = false;
        foreach ($stub2 as $index => $line) {
            $removeThis = false;
            $line = trim($line);
            if ($line === '{% if uuid %}') {
                $removing = ! $this->settings->useUuid;
                $removeThis = true;
            } elseif ($line === '{% if !uuid %}') {
                $removing = $this->settings->useUuid;
                $removeThis = true;
            } elseif ($line === '{% if useCarbon %}') {
                $removing = ! $this->settings->useCarbon;
                $removeThis = true;
            } elseif ($line === '{% endif %}') {
                $removing = false;
                $removeThis = true;
            }
            if ($removing || $removeThis) {
                unset($stub2[$index]);
            }
        }

        $stub = implode("\n", $stub2);

        $stub = Str::replaceMatches(
            ['/\s*\{% if uuid %}/', '/\s*\{% if !uuid %}/', '/\s*\{% if useCarbon %}/', '/\s*\{% endif %}/'],
            ["\n", "\n", "\n", ''],
            $stub
        );

        return $this;
    }

    public function replace(&$stub): self
    {
        return $this->replaceDomain($stub)
            ->replaceConstructorArguments($stub)
            ->replaceModelFillableArguments($stub)
            ->replaceModelCastArguments($stub)
            ->replaceProjectionArguments($stub)
            ->replaceProjectorArguments($stub)
            ->replacePrimaryKey($stub)
            ->replaceIfBlocks($stub);
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
