<?php

namespace GeneralPurposeIO\Digital\Bus;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalInputPin;
use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\Digital\Drivers\PosixDigitalIODriver;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineConfig;
use Microscrap\Bindings\GPIO\Enums\EdgeEventType;
use Microscrap\Bindings\GPIO\Enums\LineBias as LibLineBias;
use Microscrap\Bindings\GPIO\Enums\LineDirection;
use Microscrap\Bindings\GPIO\Enums\LineEdge;
use Microscrap\Bindings\POSIX\Enums\FcntlCommand;
use Microscrap\Bindings\POSIX\Enums\FileControlFlag;

class PosixDigitalIOBus extends DigitalIOBus
{
    public function __construct(
        protected PosixDigitalIODriver $driver
    ) {}

    /**
     * @throws GPIOException
     */
    public function input(int $pin, bool $rising_events = false, bool $falling_events = false, LineBias $line_bias = LineBias::AS_IS, bool $active_low = false, int $timeout_ms = -1): DigitalInputPin
    {
        $has_nonblocking_input = $timeout_ms > -1;
        $line_config = gpiod_line_config_new();
        $this->addLineSettings(
            $line_config,
            LineDirection::INPUT,
            $pin,
            $line_bias,
            $active_low,
            static::edgeEvents($rising_events, $falling_events),
        );

        $line_request = gpiod_chip_request_lines($this->driver->chip, $this->driver->req_config, $line_config);

        if (is_null($line_request)) {
            gpiod_chip_close($this->driver->chip);

            throw new GPIOException("Could not request line {$pin} on {$this->driver->chip->path}");
        }

        if ($has_nonblocking_input && function_exists('fcntl')) {
            $current_flags = 0;
            $ignored = null;
            fcntl($line_request->fd, FcntlCommand::F_GETFL->value, 0, $current_flags);
            fcntl(
                $line_request->fd,
                FcntlCommand::F_SETFL->value,
                $current_flags | FileControlFlag::O_NONBLOCK->value,
                $ignored,
            );
        }

        return new DigitalInputPin($line_request, $this->driver);
    }

    /**
     * @throws GPIOException
     */
    public function output(int $pin): DigitalOutputPin
    {
        $line_config = gpiod_line_config_new();
        $this->addLineSettings(
            $line_config,
            LineDirection::OUTPUT,
            $pin,
            LineBias::AS_IS,
            false,
        );

        $line_request = gpiod_chip_request_lines($this->driver->chip, $this->driver->req_config, $line_config);

        if (is_null($line_request)) {
            gpiod_chip_close($this->driver->chip);

            throw new GPIOException("Could not request line {$pin} on {$this->driver->chip->path}");
        }

        return new DigitalOutputPin($line_request, $this->driver);
    }

    protected function addLineSettings(
        GPIOLineConfig $line_config,
        LineDirection $direction,
        int $offset,
        LineBias $bias = LineBias::AS_IS,
        bool $active_low = false,
        array $events = [],
    ): void
    {
        $settings = gpiod_line_settings_new();
        gpiod_line_settings_set_direction($settings, $direction);
        gpiod_line_settings_set_bias($settings, LibLineBias::from($bias->value));
        gpiod_line_settings_set_active_low($settings, $active_low);

        if ($direction === LineDirection::INPUT) {
            $edge = LineEdge::NONE;
            $has_rising = in_array(EdgeEventType::RISING_EDGE, $events, true);
            $has_falling = in_array(EdgeEventType::FALLING_EDGE, $events, true);

            if ($has_rising) {
                $edge = LineEdge::RISING;
            }

            if ($has_falling) {
                $edge = ($edge === LineEdge::NONE) ? LineEdge::FALLING : LineEdge::BOTH;
            }

            gpiod_line_settings_set_edge_detection($settings, $edge);
        }

        gpiod_line_config_add_line_settings($line_config, [$offset], $settings);
    }

    /**
     * @return list<EdgeEventType>
     */
    protected static function edgeEvents(bool $rising_events, bool $falling_events): array
    {
        return array_values(array_filter([
            $rising_events ? EdgeEventType::RISING_EDGE : null,
            $falling_events ? EdgeEventType::FALLING_EDGE : null,
        ]));
    }
}