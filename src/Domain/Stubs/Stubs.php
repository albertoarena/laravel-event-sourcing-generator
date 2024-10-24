<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\Models\StubCallback;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;

class Stubs
{
    protected array $availableStubs;

    public function __construct(
        protected Application $laravel,
        protected string $domainPath,
        protected string $domainName,
        protected bool $createAggregateRoot,
        protected bool $createReactor,
    ) {
        $this->availableStubs = [];
    }

    protected function resolvePath($path): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($path, '/')))
            ? $customPath
            : realpath(__DIR__.'/../../../'.$path);
    }

    protected function getAvailableStubs(): array
    {
        if (! $this->availableStubs) {
            $this->availableStubs = File::json($this->resolvePath('stubs/stub-mapping.json'));
        }

        return $this->availableStubs;
    }

    /**
     * Get stub resolvers, based on domain settings
     */
    protected function getStubResolvers(): array
    {
        return array_filter(array_map(function ($stubResolverData) {
            $context = $stubResolverData['context'] ?? null;
            if (! $context) {
                return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
            } else {
                $reactor = $stubResolverData['context']['reactor'] ?? null;
                if (! is_null($reactor)) {
                    if ($this->createReactor === $reactor) {
                        return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
                    }

                    return false;
                }

                $aggregateRoot = $stubResolverData['context']['aggregate_root'] ?? null;
                if (is_null($aggregateRoot) || $this->createAggregateRoot === $aggregateRoot) {
                    return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
                }
            }

            return false;
        }, $this->getAvailableStubs()));
    }

    /**
     * Resolve paths of stub and output
     *
     * @throws FileNotFoundException
     */
    public function resolve(StubCallback $callback): void
    {
        /** @var StubResolver $stubResolver */
        foreach ($this->getStubResolvers() as $stubResolver) {
            $callback->call(
                ...$stubResolver->resolve($this->laravel, $this->domainPath, $this->domainName)
            );
        }
    }
}
