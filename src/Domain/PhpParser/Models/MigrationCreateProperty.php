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

    protected static function getArrayExprValues(Node\Expr\Array_ $value): array
    {
        $ret = [];
        foreach ($value->items as $item) {
            if ($item->value instanceof Node\Scalar\String_) {
                $ret[] = $item->value->value;
            }
        }

        return $ret;
    }

    protected static function exprMethodCallToTypeArgs(Node\Expr\MethodCall $expr, bool $nullable = false): array
    {
        if ($expr->var instanceof Node\Expr\MethodCall) {
            return self::exprMethodCallToTypeArgs($expr->var, $expr->name->name === 'nullable');
        }

        $type = $expr->name->name;
        $args = array_filter(
            Arr::map(
                $expr->args ?? [],
                fn (Node\Arg $arg) => $arg->value instanceof Node\Scalar\String_ ?
                    $arg->value->value :
                    (
                        $arg->value instanceof Node\Expr\Array_ ?
                        self::getArrayExprValues($arg->value) :
                        null
                    )
            )
        );

        return [$type, $args, $nullable];
    }

    /**
     * @return MigrationCreateProperty[]
     *
     * @throws MigrationInvalidPrimaryKeyException
     */
    public static function createPropertiesFromExprMethodCall(Node\Expr\MethodCall $expr): array
    {
        $warning = null;
        $droppedColumns = [];
        $renameTo = null;
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
        } elseif ($type === 'dropColumn') {
            $droppedColumns = is_array($args[0]) ? $args[0] : $args;
        } elseif ($type === 'renameColumn') {
            $renameTo = $args[1];
        }

        if ($droppedColumns) {
            return Arr::map(
                $droppedColumns,
                fn ($dropColumn) => new self(
                    $dropColumn,
                    new MigrationCreatePropertyType(
                        type: $type,
                        nullable: $nullable,
                        isIgnored: in_array($dropColumn, BlueprintUnsupportedInterface::IGNORED),
                        warning: $warning,
                        isDropped: true,
                        renameTo: $renameTo
                    ),
                )
            );
        }

        return [new self(
            $name,
            new MigrationCreatePropertyType(
                type: $type,
                nullable: $nullable,
                isIgnored: in_array($name, BlueprintUnsupportedInterface::IGNORED),
                warning: $warning,
                renameTo: $renameTo
            ),
        )];
    }
}
