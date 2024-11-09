<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Contracts;

interface AcceptedNotificationInterface
{
    public const MAIL = 'mail';

    public const SLACK = 'slack';

    public const TEAMS = 'teams';

    public const ACCEPTED = [self::MAIL, self::SLACK, self::TEAMS];
}
