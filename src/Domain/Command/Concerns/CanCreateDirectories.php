<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Concerns;

trait CanCreateDirectories
{
    /**
     * Create domain directories
     */
    protected function createDirectories(): self
    {
        $this->makeDirectory($this->settings->domainPath.'Actions/*');
        $this->makeDirectory($this->settings->domainPath.'DataTransferObjects/*');
        $this->makeDirectory($this->settings->domainPath.'Events/*');
        $this->makeDirectory($this->settings->domainPath.'Projections/*');
        $this->makeDirectory($this->settings->domainPath.'Projectors/*');

        if ($this->settings->createAggregate) {
            $this->makeDirectory($this->settings->domainPath.'Aggregates/*');
        }

        if ($this->settings->createReactor) {
            $this->makeDirectory($this->settings->domainPath.'Reactors/*');
        }

        if ($this->settings->notifications) {
            $this->makeDirectory($this->settings->domainPath.'Notifications/*');
            $this->makeDirectory($this->settings->domainPath.'Notifications/Concerns/*');
        }

        if ($this->settings->createUnitTest) {
            $this->makeDirectory($this->settings->testDomainPath.'/*');
        }

        return $this;
    }
}
