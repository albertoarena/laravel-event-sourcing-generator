<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models\CommandSettings;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;

class StubResolver
{
    public function __construct(
        public string $path,
        public string $resolverPattern,
    ) {
        if (! Str::endsWith($this->path, '.stub')) {
            $this->path .= '.stub';
        }
    }

    protected function resolvePath(Application $laravel): string
    {
        return file_exists($customPath = $laravel->basePath(trim($this->path, '/')))
            ? $customPath
            : realpath(__DIR__.'/../../../'.$this->path);
    }

    protected function resolveOutputPath(
        CommandSettings $settings
    ): string {
        return Str::replace(
            ['{{path}}', '{{name}}', '{{ path }}', '{{ name }}', '//'],
            [$settings->domainPath, $settings->model, $settings->domainPath, $settings->model, '/'],
            $this->resolverPattern
        );
    }

    public function resolve(
        Application $laravel,
        CommandSettings $settings,
    ): array {
        return [
            $this->resolvePath($laravel),
            $this->resolveOutputPath($settings),
        ];
    }
}
