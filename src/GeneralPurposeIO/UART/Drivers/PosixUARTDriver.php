<?php

namespace GeneralPurposeIO\UART\Drivers;

use GeneralPurposeIO\Contracts\UART\DataBits;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\Parity;
use GeneralPurposeIO\Contracts\UART\StopBits;
use Microscrap\Bindings\UART\DataObjects\UARTPort;
use Microscrap\Bindings\UART\Enums\ControlFlag;
use Microscrap\Bindings\UART\Enums\InputFlag;
use Microscrap\Bindings\UART\Enums\TermiosAction;

class PosixUARTDriver extends UARTDriver
{
    public function __construct(
        public readonly UARTPort $port,
    ) {}

    public function close(): void
    {
        uart_close($this->port);
    }

    public function flush(): void
    {
        uart_flush($this->port);
    }

    public function read(int $len): array|false
    {
        $data = uart_read($this->port, $len);

        if ($data === false) {
            return false;
        }

        return bytes2array($data);
    }

    public function write(array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return uart_write($this->port, $data);
    }

    public static function configureLine(
        UARTPort $port,
        DataBits $data_bits,
        Parity $parity,
        FlowControl $flow_control,
        StopBits $stop_bits,
    ): void {
        $termios = uart_tcgetattr($port);

        if ($termios === false) {
            return;
        }

        $termios['c_cflag'] &= ~ControlFlag::CS8->value;
        $termios['c_cflag'] |= match ($data_bits) {
            DataBits::FIVE => ControlFlag::CS5->value,
            DataBits::SIX => ControlFlag::CS6->value,
            DataBits::SEVEN => ControlFlag::CS7->value,
            DataBits::EIGHT => ControlFlag::CS8->value,
        };

        if ($parity === Parity::NONE) {
            $termios['c_cflag'] &= ~ControlFlag::PARENB->value;
        } else {
            $termios['c_cflag'] |= ControlFlag::PARENB->value;

            if ($parity === Parity::ODD) {
                $termios['c_cflag'] |= ControlFlag::PARODD->value;
            } else {
                $termios['c_cflag'] &= ~ControlFlag::PARODD->value;
            }
        }

        if ($stop_bits === StopBits::TWO) {
            $termios['c_cflag'] |= ControlFlag::CSTOPB->value;
        } else {
            $termios['c_cflag'] &= ~ControlFlag::CSTOPB->value;
        }

        if ($flow_control === FlowControl::HARDWARE) {
            $termios['c_cflag'] |= ControlFlag::CRTSCTS->value;
        } else {
            $termios['c_cflag'] &= ~ControlFlag::CRTSCTS->value;
        }

        $xon_xoff = InputFlag::IXON->value | InputFlag::IXOFF->value;

        if ($flow_control === FlowControl::SOFTWARE) {
            $termios['c_iflag'] |= $xon_xoff;
        } else {
            $termios['c_iflag'] &= ~$xon_xoff;
        }

        uart_tcsetattr($port, $termios, TermiosAction::TCSANOW);
    }
}
