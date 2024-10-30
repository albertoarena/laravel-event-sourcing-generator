<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Illuminate\Support\Arr;
use PhpParser\Node;

class MigrationCreateProperty
{
    use HasBlueprintColumnType;

    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
    ) {
        if (! $this->name) {
            $this->name = $this->type;
            $this->type = $this->columnTypeToBuiltInType($this->type);
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

        return new self($name, $type, $nullable);
    }
}
