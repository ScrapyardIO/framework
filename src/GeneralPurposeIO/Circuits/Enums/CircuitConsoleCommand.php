<?php

namespace GeneralPurposeIO\Circuits\Enums;

enum CircuitConsoleCommand: string
{
    case MAKE_PROFILE = 'circuit:make-profile';
    case PUBLISH_CONFIG_TAG = 'gpio-circuits-config';
}
