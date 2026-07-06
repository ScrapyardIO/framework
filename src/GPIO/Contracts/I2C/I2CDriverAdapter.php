<?php

namespace GPIO\Contracts\I2C;

use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterContract;
use GPIO\Contracts\I2C\I2CConnectionHandle as I2CConnectionHandleInterface;

interface I2CDriverAdapter extends GPIODriverAdapterContract
{
    public function buildConnection(
        int|string $master,
        int $slave,
        int|string|null $gpio_chip = null,
        array $digital_pins = []
    ): I2CTransport|GPIOConnectionBus;

    public function writeRead(
        int $slave_address,
        string|array $bytes_to_write,
        int $bytes_to_read,
        I2CConnectionHandleInterface $handle
    ): array|false;

    public function bulkWrite(
        int $slave_address,
        string|array $messages,
        I2CConnectionHandleInterface $handle
    ): array|false;

    public function read(
        int $slave_address,
        int $len,
        I2CConnectionHandleInterface $handle
    ): array|false;

    public function write(
        int $slave_address,
        string|array $data,
        I2CConnectionHandleInterface $handle)
    : int;


}
