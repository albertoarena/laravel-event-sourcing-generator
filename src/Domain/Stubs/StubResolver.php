<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs;

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
        string $domainPath,
        string $domainName
    ): string {
        return Str::replace(
            ['{{path}}', '{{name}}', '{{ path }}', '{{ name }}', '//'],
            [$domainPath, $domainName, $domainPath, $domainName, '/'],
            $this->resolverPattern
        );
    }

    public function resolve(
        Application $laravel,
        string $domainPath,
        string $domainName
    ): array {
        return [
            $this->resolvePath($laravel),
            $this->resolveOutputPath($domainPath, $domainName),
        ];
    }
}
