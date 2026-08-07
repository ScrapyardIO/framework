<?php

namespace Fabricate\Database\Migrations;

enum MigrationResult: int
{
    case SUCCESS = 1;
    case FAILURE = 2;
    case SKIPPED = 3;
}
