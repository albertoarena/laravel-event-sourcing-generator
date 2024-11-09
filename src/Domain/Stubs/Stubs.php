<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models\CommandSettings;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\Models\StubCallback;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;

class Stubs
{
    protected array $availableStubs;

    public function __construct(
        protected Application $laravel,
        protected CommandSettings $settings,
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
                $test = $stubResolverData['context']['test'] ?? null;
                if (! is_null($test)) {
                    if ($this->settings->createUnitTest) {
                        return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
                    }

                    return false;
                }

                $notifications = $stubResolverData['context']['failed_event'] ?? null;
                if (! is_null($notifications)) {
                    if ($this->settings->createFailedEvents) {
                        return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
                    }

                    return false;
                }

                $notifications = $stubResolverData['context']['notifications'] ?? null;
                if (! is_null($notifications)) {
                    if ($this->settings->notifications) {
                        return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
                    }

                    return false;
                }

                $reactor = $stubResolverData['context']['reactor'] ?? null;
                if (! is_null($reactor)) {
                    if ($this->settings->createReactor === $reactor) {
                        return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
                    }

                    return false;
                }

                $aggregateRoot = $stubResolverData['context']['aggregate_root'] ?? null;
                if (is_null($aggregateRoot) || $this->settings->createAggregateRoot === $aggregateRoot) {
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
                ...$stubResolver->resolve($this->laravel, $this->settings)
            );
        }
    }
}
