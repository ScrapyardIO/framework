<?php

namespace Waveforms\Carriers\UART\API\Native\Factory;

use Microscrap\Bindings\UART\DataObjects\UARTPort;
use Microscrap\Bindings\UART\Enums\ControlFlag;
use Microscrap\Bindings\UART\Enums\InputFlag;
use Microscrap\Bindings\UART\Enums\TermiosAction;
use Waveforms\Carriers\UART\API\Native\Exceptions\NativeUARTException;
use Waveforms\Carriers\UART\API\Native\NativeUARTDevice;
use Waveforms\Carriers\UART\Enums\DataBits;
use Waveforms\Carriers\UART\Enums\FlowControl;
use Waveforms\Carriers\UART\Enums\Parity;
use Waveforms\Carriers\UART\Enums\StopBits;
use Waveforms\Carriers\UART\Exceptions\UARTException;
use Waveforms\Carriers\UART\Factory\UARTConnectionBuilder;

class NativeUARTConnectionBuilder extends UARTConnectionBuilder
{
    public ?string $port_path = null;

    public function port(string $path): static
    {
        $this->port_path = $path;

        return $this;
    }

    public function boot(): NativeUARTDevice
    {
        if (is_null($this->port_path)) {
            throw NativeUARTException::missingPort();
        }

        $port = uart_open($this->port_path, $this->baud_rate);

        if (is_null($port)) {
            throw UARTException::couldNotOpenUARTPort($this->port_path);
        }

        $this->configureLine($port);

        return new NativeUARTDevice($port);
    }

    /**
     * Apply the data-bit, parity, stop-bit and flow-control settings on top of
     * the raw 8N1 line that uart_open() establishes by default.
     */
    private function configureLine(UARTPort $port): void
    {
        $termios = uart_tcgetattr($port);

        if ($termios === false) {
            return;
        }

        $termios['c_cflag'] &= ~ControlFlag::CS8->value;
        $termios['c_cflag'] |= match ($this->data_bits) {
            DataBits::FIVE => ControlFlag::CS5->value,
            DataBits::SIX => ControlFlag::CS6->value,
            DataBits::SEVEN => ControlFlag::CS7->value,
            DataBits::EIGHT => ControlFlag::CS8->value,
        };

        if ($this->parity === Parity::NONE) {
            $termios['c_cflag'] &= ~ControlFlag::PARENB->value;
        } else {
            $termios['c_cflag'] |= ControlFlag::PARENB->value;

            if ($this->parity === Parity::ODD) {
                $termios['c_cflag'] |= ControlFlag::PARODD->value;
            } else {
                $termios['c_cflag'] &= ~ControlFlag::PARODD->value;
            }
        }

        if ($this->stop_bits === StopBits::TWO) {
            $termios['c_cflag'] |= ControlFlag::CSTOPB->value;
        } else {
            $termios['c_cflag'] &= ~ControlFlag::CSTOPB->value;
        }

        if ($this->flow_control === FlowControl::HARDWARE) {
            $termios['c_cflag'] |= ControlFlag::CRTSCTS->value;
        } else {
            $termios['c_cflag'] &= ~ControlFlag::CRTSCTS->value;
        }

        $xon_xoff = InputFlag::IXON->value | InputFlag::IXOFF->value;

        if ($this->flow_control === FlowControl::SOFTWARE) {
            $termios['c_iflag'] |= $xon_xoff;
        } else {
            $termios['c_iflag'] &= ~$xon_xoff;
        }

        uart_tcsetattr($port, $termios, TermiosAction::TCSANOW);
    }

    public function connection(): string
    {
        return 'native';
    }
}
