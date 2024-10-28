<?php

namespace Tests\Domain\PhpParser;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\MigrationParser;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\ParserFailedException;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;
use Tests\Domain\PhpParser\Traversers\BlueprintClassModifyNodeVisitor;

class MigrationModifyParser extends MigrationParser
{
    use HasBlueprintColumnType;

    public function __construct(
        protected ?string $migrationContent,
        protected array $injectProperties,
        protected array $options = [],
    ) {
        parent::__construct($migrationContent);
    }

    protected function getTraverser(): NodeTraverser
    {
        $traverser = new NodeTraverser;
        $traverser->addVisitor(
            new BlueprintClassModifyNodeVisitor(
                $this->injectProperties,
                $this->options
            )
        );

        return $traverser;
    }

    /**
     * WARNING!!
     * This is not intended to be used in production but only for test purposes.
     * It will modify the original migration.
     *
     * @throws ParserFailedException
     */
    public function modify(): string
    {
        $statements = $this->getStatements();

        $this->getTraverser()->traverse($statements);

        return (new PrettyPrinter\Standard)->prettyPrintFile($statements);
    }
}
