<?php

namespace Tests;

use Albertoarena\LaravelDomainGenerator\Providers\PackageServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            PackageServiceProvider::class,
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->afterApplicationCreated(function () {
            File::cleanDirectory(app_path());
            File::cleanDirectory(database_path('migrations'));
        });
    }
}
