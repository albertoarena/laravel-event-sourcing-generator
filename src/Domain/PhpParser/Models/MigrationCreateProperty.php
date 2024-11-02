<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Illuminate\Support\Arr;
use PhpParser\Node;

class MigrationCreateProperty
{
    use HasBlueprintColumnType;

    public MigrationCreatePropertyType $type;

    public function __construct(
        public string $name,
        string|MigrationCreatePropertyType $type,
    ) {
        $this->type = $type instanceof MigrationCreatePropertyType ?
            $type :
            new MigrationCreatePropertyType($type);

        if (! $this->name) {
            $this->name = $this->type->type;
            $this->type->setAsBuiltInType();
        }
    }

    protected static function exprMethodCallToTypeArgs(Node\Expr\MethodCall $expr, bool $nullable = false): array
    {
        if ($expr->var instanceof Node\Expr\MethodCall) {
            return self::exprMethodCallToTypeArgs($expr->var, $expr->name->name === 'nullable');
        }

        $type = $expr->name->name;
        $args = Arr::map($expr->args ?? [], fn (Node\Arg $arg) => $arg->value instanceof Node\Scalar\String_ ? $arg->value->value : null);

        return [$type, $args, $nullable];
    }

    public static function createFromExprMethodCall(Node\Expr\MethodCall $expr): self
    {
        [$type, $args, $nullable] = self::exprMethodCallToTypeArgs($expr);

        if (! $args) {
            $name = '';
        } else {
            $name = $args[0];
        }

        return new self(
            $name,
            new MigrationCreatePropertyType($type, $nullable),
        );
    }
}
