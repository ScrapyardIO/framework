<?php

namespace GeneralPurposeIO\Core\Extensions;

enum PiePackage: string
{
    case BINARY = 'pie';
    case PHAR_URL = 'https://github.com/php/pie/releases/latest/download/pie.phar';
    case INSTALL_DIRECTORY = '.local/bin';
    case VERSION_MARKER = '(PIE)';
}
