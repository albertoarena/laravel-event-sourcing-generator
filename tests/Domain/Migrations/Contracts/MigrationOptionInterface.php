<?php

namespace Tests\Domain\Migrations\Contracts;

interface MigrationOptionInterface
{
    const INJECTS = ':injects';

    const PRIMARY_KEY = ':primary';

    const SOFT_DELETES = ':soft_deletes';
}
