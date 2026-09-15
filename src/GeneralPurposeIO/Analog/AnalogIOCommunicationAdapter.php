<?php

namespace GeneralPurposeIO\Analog;

use GeneralPurposeIO\Common\GPIOCommunicationAdapter;
use GeneralPurposeIO\Contracts\UART\UARTCommunicationAdapter as AdapterContract;

abstract class AnalogIOCommunicationAdapter extends GPIOCommunicationAdapter implements AdapterContract
{

}