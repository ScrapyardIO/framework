<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

enum RefreshMode: string
{
    case FULL = 'full';
    case PARTIAL = 'partial';
}
