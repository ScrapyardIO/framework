<?php

namespace Fabricate\Database\Polisher;

enum ModelTimestampColumn: string
{
    case CREATED_AT = 'created_at';
    case UPDATED_AT = 'updated_at';
}
