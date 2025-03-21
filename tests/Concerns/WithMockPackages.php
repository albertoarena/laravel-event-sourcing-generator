<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use Spatie\EventSourcing\EventSourcingServiceProvider;

trait WithMockPackages
{
    protected function hideSpatiePackage(): void
    {
        PHPMockery::mock('Albertoarena\LaravelEventSourcingGenerator\Console\Commands', 'class_exists')
            ->andReturnUsing(function ($class) {
                return ! ($class === EventSourcingServiceProvider::class) && class_exists($class);
            });
    }

    protected function mockMicrosoftTeamsPackage(): void
    {
        PHPMockery::mock('Albertoarena\LaravelEventSourcingGenerator\Console\Commands', 'class_exists')
            ->andReturnUsing(function ($class) {
                return $class === 'NotificationChannels\MicrosoftTeams\MicrosoftTeamsChannel' || class_exists($class);
            });
    }

    protected function hideMicrosoftTeamsPackage(): void
    {
        PHPMockery::mock('Albertoarena\LaravelEventSourcingGenerator\Console\Commands', 'class_exists')
            ->andReturnUsing(function ($class) {
                return ! ($class === 'NotificationChannels\MicrosoftTeams\MicrosoftTeamsChannel') && class_exists($class);
            });
    }

    protected function mockSlackPackage(): void
    {
        PHPMockery::mock('Albertoarena\LaravelEventSourcingGenerator\Console\Commands', 'class_exists')
            ->andReturnUsing(function ($class) {
                return $class === 'Illuminate\Notifications\Slack\SlackMessage' || class_exists($class);
            });
    }

    protected function hideSlackPackage(): void
    {
        PHPMockery::mock('Albertoarena\LaravelEventSourcingGenerator\Console\Commands', 'class_exists')
            ->andReturnUsing(function ($class) {
                return ! ($class === 'Illuminate\Notifications\Slack\SlackMessage') && class_exists($class);
            });
    }

    protected function hidePhpunitPackage(): void
    {
        PHPMockery::mock('Albertoarena\LaravelEventSourcingGenerator\Console\Commands', 'class_exists')
            ->andReturnUsing(function ($class) {
                return ! ($class === TestCase::class) && class_exists($class);
            });
    }

    protected function withNotificationsTable(): void
    {
        Artisan::call('make:notifications-table');
        Artisan::call('migrate');
    }
}
