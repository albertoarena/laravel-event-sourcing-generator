<?php

namespace Tests;

use Albertoarena\LaravelEventSourcingGenerator\Providers\PackageServiceProvider;
use Illuminate\Support\Facades\File;
use Mockery;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterApplicationCreated(function () {
            File::cleanDirectory(app_path());
            File::cleanDirectory(database_path('migrations'));
            File::cleanDirectory(base_path('tests/Unit'));
            File::cleanDirectory(base_path('storage/logs'));
            if (File::exists(base_path('src'))) {
                File::cleanDirectory(base_path('src'));
                File::deleteDirectory(base_path('src'));
            }
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }
}
