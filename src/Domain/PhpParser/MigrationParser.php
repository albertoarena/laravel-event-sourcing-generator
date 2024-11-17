<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Traversers\BlueprintClassNodeVisitor;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\ParserFailedException;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class MigrationParser
{
    protected array $properties;

    protected array $ignored;

    public function __construct(
        protected ?string $migrationContent,
    ) {
        $this->properties = [];
        $this->ignored = [];
    }

    protected function getTraverser(): NodeTraverser
    {
        $traverser = new NodeTraverser;
        $traverser->addVisitor(
            new BlueprintClassNodeVisitor($this->properties, $this->ignored)
        );

        return $traverser;
    }

    /**
     * @throws ParserFailedException
     */
    protected function getStatements(): ?array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        try {
            return $parser->parse($this->migrationContent);
        } catch (Error $error) {
            throw new ParserFailedException($error->getMessage());
        }
    }

    /**
     * @throws ParserFailedException
     */
    public function parse(): self
    {
        $this->getTraverser()->traverse($this->getStatements());

        return $this;
    }

    public function getProperties(): array
    {
        return array_values($this->properties);
    }

    public function getIgnored(): array
    {
        return array_values($this->ignored);
    }
}
