<?php

namespace Albertoarena\LaravelDomainGenerator\Providers;

use Albertoarena\LaravelDomainGenerator\Console\Commands\DomainMakeCommand;
use Illuminate\Support\ServiceProvider;

final class PackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                commands: [
                    DomainMakeCommand::class,
                ],
            );
        }
    }
}
