<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts\BlueprintUnsupportedInterface;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\MigrationInvalidPrimaryKeyException;
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
            new MigrationCreatePropertyType(
                type: $type,
                isIgnored: in_array($name, BlueprintUnsupportedInterface::IGNORED)
            );

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
        $args = array_filter(Arr::map($expr->args ?? [], fn (Node\Arg $arg) => $arg->value instanceof Node\Scalar\String_ ? $arg->value->value : null));

        return [$type, $args, $nullable];
    }

    /**
     * @throws MigrationInvalidPrimaryKeyException
     */
    public static function createFromExprMethodCall(Node\Expr\MethodCall $expr): self
    {
        $warning = null;
        [$type, $args, $nullable] = self::exprMethodCallToTypeArgs($expr);

        if (! $args) {
            $name = '';
        } else {
            $name = $args[0];
        }

        // If $table->uuid('id') is used, cannot parse migration
        if ($type === 'uuid' && $name === 'id') {
            // Bad setup, cannot parse migration
            throw new MigrationInvalidPrimaryKeyException;
        } elseif ($type === 'primary') {
            // Auto-adjust primary but store warning
            $name = 'id';
            $type = 'integer';
            $first = Arr::first($expr->args);
            if ($first && $first->value instanceof Node\Expr\Array_) {
                $warning = 'Composite keys are not supported for primary key';
            } else {
                $warning = 'Type not supported for primary key';
            }
        }

        return new self(
            $name,
            new MigrationCreatePropertyType(
                type: $type,
                nullable: $nullable,
                isIgnored: in_array($name, BlueprintUnsupportedInterface::IGNORED),
                warning: $warning,
            ),
        );
    }
}
