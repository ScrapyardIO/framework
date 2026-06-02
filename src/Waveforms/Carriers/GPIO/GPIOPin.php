<?php

namespace Waveforms\Carriers\GPIO;

use Exception;
use Waveforms\Carriers\GPIO\Contracts\GPIOInput;
use Waveforms\Carriers\GPIO\Contracts\GPIOOutput;

class GPIOPin
{
    public string $alias = 'scrapyard-io';

    public function alias(string $name): static
    {
        $this->alias = $name;

        return $this;
    }

    /**
     * @throws Exception
     */
    public static function createInput(string $connection, int $pin, string $alias): GPIOInput
    {
        return GPIOService::getInstance()->getInput($connection, $pin)->alias($alias);
    }

    /**
     * @throws Exception
     */
    public static function createOutput(string $connection, int $pin, string $alias): GPIOOutput
    {
        return GPIOService::getInstance()->getOutput($connection, $pin)->alias($alias);
    }
}
