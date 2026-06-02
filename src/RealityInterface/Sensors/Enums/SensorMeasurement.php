<?php

namespace RealityInterface\Sensors\Enums;

enum SensorMeasurement: string
{
    case NO_OP = 'no-op';
    case DISTANCE = 'distance';
    case ACCELERATION = 'acceleration';
    case TILT = 'tilt';
    case MAGNITUDE = 'magnitude';
    case FREEFALL = 'freefall';
    case ACTIVITY = 'activity';
    case VIBRATION = 'vibration';
    case IMPACT = 'impact';
    case TEMPERATURE = 'temperature';
    case RELATIVE_HUMIDITY = 'rh';
    case BAROMETRIC_PRESSURE = 'barometric-pressure';
    case OBJECT_IDENTIFICATION = 'object-identification';
    case LUMINOUS_INTENSITY = 'luminous-intensity';
    case LUMINOUS_FLUX = 'luminous-flux';
    case ILLUMINANCE = 'illuminance';
    case IRRADIANCE = 'irradiance';
}
