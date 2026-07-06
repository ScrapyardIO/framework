<?php

namespace GPIO\UART;

use GPIO\Common\GPIOTransport;
use GPIO\Contracts\UART\UARTTransport as UARTTransportContract;
use GPIO\Contracts\UART\UARTDriverAdapter as UARTDriverAdapterInterface;
use GPIO\Contracts\UART\UARTConnectionHandle as UARTConnectionHandleInterface;

class UART extends GPIOTransport implements UARTTransportContract
{
    public function __construct(
        UARTConnectionHandleInterface $handle,
        UARTDriverAdapterInterface $driver,
    ) {
        parent::__construct($driver, $handle);
    }

    public function read(int $len): array|false
    {
        return $this->driver()->read($len,$this->handle());
    }

    public function write(array|string $data): int
    {
        return $this->driver()->write($data,$this->handle());
    }

    public function flush(): void
    {
        $this->driver()->flush($this->handle());
    }

    protected function driver(): UARTDriverAdapterInterface
    {
        /** @var UARTDriverAdapterInterface */
        return $this->driver;
    }

    protected function handle(): UARTConnectionHandleInterface
    {
        /** @var UARTConnectionHandleInterface */
        return $this->handle;
    }
}
