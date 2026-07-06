<?php

namespace GPIO\I2C;

use GPIO\Common\GPIOTransport;
use GPIO\Contracts\I2C\I2CTransport as I2CTransportContract;
use GPIO\Contracts\I2C\I2CDriverAdapter as I2CDriverAdapterInterface;
use GPIO\Contracts\I2C\I2CConnectionHandle as I2CConnectionHandleInterface;

class I2C extends GPIOTransport implements I2CTransportContract
{
    public function __construct(
        I2CConnectionHandleInterface $handle,
        I2CDriverAdapterInterface $driver,
    ) {
        parent::__construct($driver, $handle);
    }

    public function read(int $len): array|false
    {
        return $this->driver()->read($this->slaveAddress(), $len, $this->handle());
    }

    public function write(array|string $data): int
    {
        return $this->driver()->write( $this->slaveAddress(), $data, $this->handle());
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return $this->driver()->writeRead($this->slaveAddress(),$bytes_to_write, $bytes_to_read, $this->handle());
    }

    public function bulkWrite(array|string $messages): array|false
    {
        return $this->driver()->bulkWrite($this->slaveAddress(), $messages, $this->handle());
    }

    protected function slaveAddress(): int
    {
        return $this->handle()->slaveAddress();
    }

    protected function driver(): I2CDriverAdapterInterface
    {
        /** @var I2CDriverAdapterInterface */
        return $this->driver;
    }

    protected function handle(): I2CConnectionHandleInterface
    {
        /** @var I2CConnectionHandleInterface */
        return $this->handle;
    }
}
