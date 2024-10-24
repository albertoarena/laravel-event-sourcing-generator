<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Traversers\BlueprintClassCreateSchemaNodeVisitor;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Traversers\BlueprintClassNodeVisitor;
use Exception;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

class MigrationParser
{
    use HasBlueprintColumnType;

    protected array $properties;

    protected array $injectProperties;

    protected array $options;

    public function __construct(
        protected ?string $migrationContent,
    ) {
        $this->properties = [];
        $this->injectProperties = [];
        $this->options = [];
    }

    protected function getBlueprintCreateSchemaTraverser(): NodeTraverser
    {
        $createSchemaTraverser = new NodeTraverser;
        $createSchemaTraverser->addVisitor(
            new BlueprintClassCreateSchemaNodeVisitor($this->properties)
        );

        return $createSchemaTraverser;
    }

    protected function getBlueprintClassTraverser(): NodeTraverser
    {
        $mainTraverser = new NodeTraverser;
        $mainTraverser->addVisitor(
            new BlueprintClassNodeVisitor(
                $this->getBlueprintCreateSchemaTraverser(),
                $this->injectProperties,
                $this->options
            )
        );

        return $mainTraverser;
    }

    /**
     * @throws Exception
     */
    protected function getStatements(): ?array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        try {
            return $parser->parse($this->migrationContent);
        } catch (Error $error) {
            throw new Exception('Parser failed: '.$error->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function parse(): self
    {
        $statements = $this->getStatements();

        $this->getBlueprintClassTraverser()->traverse($statements);

        return $this;
    }

    /**
     * WARNING!!
     * This is not intended to be used in production but only for test purposes.
     * It will modify the original migration.
     *
     * @throws Exception
     */
    public function modify(array $injectProperties, array $options): string
    {
        $this->injectProperties = $injectProperties;
        $this->options = $options;

        $statements = $this->getStatements();

        $this->getBlueprintClassTraverser()->traverse($statements);

        $prettyPrinter = new PrettyPrinter\Standard;

        return $prettyPrinter->prettyPrintFile($statements);
    }

    public function getProperties(): array
    {
        return array_values($this->properties);
    }
}
