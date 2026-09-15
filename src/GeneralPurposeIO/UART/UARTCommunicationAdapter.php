<?php

namespace GeneralPurposeIO\UART;

use GeneralPurposeIO\Common\GPIOCommunicationAdapter;
use GeneralPurposeIO\Contracts\UART\UARTCommunicationAdapter as AdapterContract;

abstract class UARTCommunicationAdapter extends GPIOCommunicationAdapter implements AdapterContract
{

}