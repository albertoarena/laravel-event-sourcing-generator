<?php

namespace Tests\Mocks;

use Illuminate\Contracts\Filesystem\Filesystem;

class MockFilesystem implements Filesystem
{
    protected static array $mockDisk = [];

    public function delete($paths): bool
    {
        return false;
    }

    public function put($path, $contents, $options = []): bool
    {
        return false;
    }

    public function exists($path): bool
    {
        return false;
    }

    public function get($path): ?string
    {
        return null;
    }

    public function json($path, $flags = 0): ?array
    {
        return null;
    }

    /**
     * @return resource|null The path resource or null on failure.
     */
    public function readStream($path) {}

    public function writeStream($path, $resource, array $options = []): bool
    {
        return false;
    }

    public function getVisibility($path): string
    {
        return '';
    }

    public function setVisibility($path, $visibility): bool
    {
        return false;
    }

    public function prepend($path, $data): bool
    {
        return false;
    }

    public function append($path, $data): bool
    {
        return false;
    }

    public function copy($from, $to): bool
    {
        return false;
    }

    public function move($from, $to): bool
    {
        return false;
    }

    public function size($path): int
    {
        return 0;
    }

    public function lastModified($path): int
    {
        return 0;
    }

    public function files($directory = null, $recursive = false): array
    {
        return [];
    }

    public function allFiles($directory = null): array
    {
        return [];
    }

    public function directories($directory = null, $recursive = false): array
    {
        return [];
    }

    public function allDirectories($directory = null): array
    {
        return [];
    }

    public function makeDirectory($path): bool
    {
        return false;
    }

    public function deleteDirectory($directory): bool
    {
        return false;
    }

    public function path($path): string
    {
        return '';
    }

    public function putFile($path, $file = null, $options = []): string|false
    {
        return false;
    }

    public function putFileAs($path, $file, $name = null, $options = []): string|false
    {
        return false;
    }
}
