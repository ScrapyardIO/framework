<?php

namespace RealityInterface\Sensors\Enums;

enum PressureUnit: string
{
    case PA = 'pa';
    case HPA = 'hPa';
    case MBAR = 'mbar';
    case ATM = 'atm';
    case MM_MERCURY = 'mmHg';
    case PSI = 'psi';
}
