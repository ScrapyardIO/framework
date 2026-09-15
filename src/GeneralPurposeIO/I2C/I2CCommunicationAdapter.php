<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Common\GPIOCommunicationAdapter;
use GeneralPurposeIO\Contracts\I2C\I2CCommunicationAdapter as AdapterContract;

abstract class I2CCommunicationAdapter extends GPIOCommunicationAdapter implements AdapterContract
{

}