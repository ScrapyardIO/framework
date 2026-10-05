<?php

namespace GeneralPurposeIO\Core\Modules;

enum ComposerPackage: string
{
    case BINARY = 'composer';
    case PHAR = 'composer.phar';
    case VERSION_MARKER = 'Composer';
}
