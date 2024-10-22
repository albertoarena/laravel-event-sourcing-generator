<?php

namespace Albertoarena\LaravelDomainGenerator\Domain\PhpParser;

use Albertoarena\LaravelDomainGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Traversers\BlueprintClassCreateSchemaNodeVisitor;
use Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Traversers\BlueprintClassNodeVisitor;
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

    protected string $injectPrimaryKey;

    public function __construct(
        protected ?string $migrationContent,
    ) {
        $this->properties = [];
        $this->injectPrimaryKey = '';
        $this->injectProperties = [];
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
                $this->injectPrimaryKey,
                $this->injectProperties
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
     * @throws Exception
     */
    public function injectProperties(array $properties, string $primaryKey): string
    {
        $this->injectPrimaryKey = $primaryKey;
        $this->injectProperties = $properties;
        $statements = $this->getStatements();

        $this->getBlueprintClassTraverser()->traverse($statements);

        $prettyPrinter = new PrettyPrinter\Standard;

        return $prettyPrinter->prettyPrintFile($statements);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
