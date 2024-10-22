<?php

namespace Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Models;

use Albertoarena\LaravelDomainGenerator\Concerns\HasBlueprintColumnType;
use Illuminate\Support\Arr;
use PhpParser\Node;

class MigrationCreateProperty
{
    use HasBlueprintColumnType;

    public function __construct(
        public string $name,
        public string $type,
    ) {
        if (! $this->name) {
            $this->name = $this->type;
            $this->type = $this->columnNameToType($this->type);
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    public static function createFromExprMethodCall(Node\Expr\MethodCall $expr): self
    {
        $type = $expr->name->name;
        $args = Arr::map($expr->args ?? [], fn (Node\Arg $arg) => $arg->value instanceof Node\Scalar\String_ ? $arg->value->value : null);

        if (! $args) {
            $name = '';
        } else {
            $name = $args[0];
        }

        return new self($name, $type);
    }
}
