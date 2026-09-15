<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Common\GPIOCommunicationAdapter;
use GeneralPurposeIO\Contracts\PWM\PWMCommunicationAdapter as AdapterContract;

abstract class PWMCommunicationAdapter extends GPIOCommunicationAdapter implements AdapterContract
{

}