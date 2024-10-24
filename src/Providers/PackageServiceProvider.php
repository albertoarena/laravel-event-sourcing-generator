<?php

namespace Albertoarena\LaravelDomainGenerator\Providers;

use Albertoarena\LaravelDomainGenerator\Console\Commands\MakeEventSourcingDomainCommand;
use Illuminate\Support\ServiceProvider;

final class PackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                commands: [
                    MakeEventSourcingDomainCommand::class,
                ],
            );
        }
    }
}
