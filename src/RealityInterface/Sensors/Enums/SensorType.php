<?php

namespace RealityInterface\Sensors\Enums;

enum SensorType: string
{
    case DUMMY = 'dummy';
    case ULTRASONIC = 'ultrasonic';
    case TIME_OF_FLIGHT = 'cm';
    case DISTANCE = 'distance';
    case ACCELEROMETER = 'accelerometer';
    case TEMPERATURE = 'temperature';
    case RELATIVE_HUMIDITY = 'rh';
    case BAROMETER = 'barometer';
    case RFID = 'rfid';
    case LUX = 'lux';
}
