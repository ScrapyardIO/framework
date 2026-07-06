<?php

namespace GPIO\SPI;

use GPIO\Common\GPIOTransport;
use GPIO\Contracts\SPI\SPITransport as SPITransportContract;
use GPIO\Contracts\SPI\SPIDriverAdapter as SPIDriverAdapterInterface;
use GPIO\Contracts\SPI\SPIConnectionHandle as SPIConnectionHandleInterface;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;

class SPI extends GPIOTransport implements SPITransportContract
{
    public function __construct(
        SPIConnectionHandleInterface $handle,
        SPIDriverAdapterInterface $driver,
    ) {
        parent::__construct($driver, $handle);
    }

    public function transfer(array|string $data): array|false
    {
        return $this->driver()->transfer($data, $this->handle());
    }

    public function read(int $len): array|false
    {
        return $this->driver()->read($len, $this->handle());
    }

    public function write(array|string $data): int
    {
        return $this->driver()->write($data, $this->handle());
    }

    protected function driver(): SPIDriverAdapterInterface
    {
        /** @var SPIDriverAdapterInterface */
        return $this->driver;
    }

    protected function handle(): SPIConnectionHandleInterface
    {
        /** @var SPIConnectionHandleInterface */
        return $this->handle;
    }
}
