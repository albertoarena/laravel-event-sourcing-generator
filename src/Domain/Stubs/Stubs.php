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

            // Unit tests
            $test = $context['unit-test'] ?? null;
            if (! is_null($test) && ! $this->settings->createUnitTest) {
                return false;
            }

            // Failed events
            $failedEvent = $context['failed-events'] ?? null;
            if (! is_null($failedEvent) && ! $this->settings->createFailedEvents) {
                return false;
            }

            // Notifications
            $notifications = $context['notifications'] ?? null;
            if (! is_null($notifications)) {
                if (! $this->settings->notifications) {
                    return false;
                } elseif (is_array($notifications) && ! array_intersect($notifications, $this->settings->notifications)) {
                    return false;
                }
            }

            // Reactor
            $reactor = $context['reactor'] ?? null;
            if (! is_null($reactor) && $this->settings->createReactor !== $reactor) {
                return false;
            }

            // Aggregate root
            $aggregate = $context['aggregate'] ?? null;
            if (! is_null($aggregate) && $this->settings->createAggregate !== $aggregate) {
                return false;
            }

            return new StubResolver($stubResolverData['stub'], $stubResolverData['output']);
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
