<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Digital\DigitalPinException;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalInput;
use GeneralPurposeIO\Digital\DigitalOutput;
use GeneralPurposeIO\Digital\DigitalPin;
use GeneralPurposeIO\Digital\Drivers\PosixDigitalPinDriver;
use GeneralPurposeIO\Digital\MultipleDigitalPins;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineConfig;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;
use Microscrap\Bindings\GPIO\Enums\EdgeEventType;
use Microscrap\Bindings\GPIO\Enums\LineBias as LibLineBias;
use Microscrap\Bindings\GPIO\Enums\LineDirection;
use Microscrap\Bindings\GPIO\Enums\LineEdge;
use Microscrap\Bindings\POSIX\Enums\FcntlCommand;
use Microscrap\Bindings\POSIX\Enums\FileControlFlag;

abstract class PosixDigitalPinDriverFactory
{
    public ?int $pin = null;

    public int|string|null $device = null;

    public ?string $name = 'scrapyard-io-gpio';

    /** @var list<PosixDigitalPinDriverFactory> */
    public array $addl_pins = [];

    abstract public function create(): DigitalPin|MultipleDigitalPins;

    public function pin(int $value): static
    {
        $this->pin = $value;

        return $this;
    }

    public function name(string $value): static
    {
        $this->name = $value;

        return $this;
    }

    public function device(int|string $value): static
    {
        $this->device = $value;

        return $this;
    }

    /**
     * @param  list<PosixDigitalPinDriverFactory>  $addl_pins
     */
    public function createWith(int|string $device, array $addl_pins): MultipleDigitalPins
    {
        $this->addl_pins = $addl_pins;

        /** @var MultipleDigitalPins */
        return $this->device($device)->create();
    }

    protected function consumer(): string
    {
        return $this->name ?? 'scrapyard-io-gpio';
    }

    /**
     * @throws DigitalPinException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw DigitalPinException::missingDigitalPinDevice();
        }

        if (is_null($this->pin)) {
            throw DigitalPinException::missingDigitalPinOffset();
        }
    }

    /**
     * Configure every line on one config, request once, then wrap pins on a shared driver.
     * Order matches BundlesPosixDigitalPins — never request_lines before addl settings.
     *
     * @throws DigitalPinException
     * @throws GPIOException
     */
    protected function createPosixPinBus(): MultipleDigitalPins
    {
        return $this->bundlePeerFactories(
            (int) $this->device,
            $this->consumer(),
            [$this, ...$this->addl_pins],
        );
    }

    /**
     * Bundle peer pin factories onto one gpiochip line request (I2C/SPI ride-alongs).
     *
     * @param  list<PosixDigitalPinDriverFactory>  $factories
     *
     * @throws DigitalPinException
     * @throws GPIOException
     */
    public function bundlePeerFactories(
        int $gpio_chip,
        string $consumer,
        array $factories,
    ): MultipleDigitalPins {
        if (count($factories) === 0) {
            throw new DigitalPinException('Posix pin bus requires at least one pin factory.');
        }

        $line_config = gpiod_line_config_new();
        $has_nonblocking_input = false;

        foreach ($factories as $factory) {
            $this->appendFactoryLine($line_config, $factory, $has_nonblocking_input);
        }

        $line_request = $this->createLineRequest(
            $gpio_chip,
            $consumer,
            $line_config,
            $has_nonblocking_input,
        );

        $driver = new PosixDigitalPinDriver($line_request);
        $pins = [];

        foreach ($factories as $factory) {
            $pins[$factory->consumer()] = $this->instantiatePosixPin($factory, $driver);
        }

        return new MultipleDigitalPins($pins);
    }

    /**
     * @param  list<PosixDigitalPinDriverFactory>  $factories
     *
     * @throws DigitalPinException
     * @throws GPIOException
     */
    public static function bundle(
        int $gpio_chip,
        string $consumer,
        array $factories,
    ): MultipleDigitalPins {
        return (new PosixDigitalOutputDriverFactory())
            ->bundlePeerFactories($gpio_chip, $consumer, $factories);
    }

    /**
     * @throws DigitalPinException
     */
    protected function appendFactoryLine(
        GPIOLineConfig $line_config,
        PosixDigitalPinDriverFactory $factory,
        bool &$has_nonblocking_input,
    ): void {
        if (is_null($factory->pin)) {
            throw DigitalPinException::missingDigitalPinOffset();
        }

        if ($factory instanceof PosixDigitalOutputDriverFactory) {
            $this->addLineSettings(
                $line_config,
                LineDirection::OUTPUT,
                $factory->pin,
                LineBias::AS_IS,
                false,
            );

            return;
        }

        if ($factory instanceof PosixDigitalInputDriverFactory) {
            $nonblocking = $factory->timeout_ms > -1;

            $this->addLineSettings(
                $line_config,
                LineDirection::INPUT,
                $factory->pin,
                $factory->line_bias,
                $factory->active_low,
                static::edgeEvents($factory->rising_events, $factory->falling_events),
            );

            if ($nonblocking) {
                $has_nonblocking_input = true;
            }

            return;
        }

        throw new DigitalPinException('Unsupported POSIX digital pin factory for pin bus.');
    }

    protected function instantiatePosixPin(
        PosixDigitalPinDriverFactory $factory,
        PosixDigitalPinDriver $driver,
    ): DigitalPin {
        if ($factory instanceof PosixDigitalOutputDriverFactory) {
            return new DigitalOutput(
                $factory->pin,
                $factory->consumer(),
                $driver,
                $factory->default_state,
            );
        }

        if ($factory instanceof PosixDigitalInputDriverFactory) {
            return new DigitalInput(
                $factory->pin,
                $factory->consumer(),
                $driver,
                $factory->timeout_ms,
                $factory->rising_events,
                $factory->falling_events,
            );
        }

        throw new DigitalPinException('Unsupported POSIX digital pin factory for pin bus.');
    }

    /**
     * @param  list<EdgeEventType>  $events
     */
    protected function addLineSettings(
        GPIOLineConfig $line_config,
        LineDirection $direction,
        int $offset,
        LineBias $bias = LineBias::AS_IS,
        bool $active_low = false,
        array $events = [],
    ): void {
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

    /**
     * @throws GPIOException
     */
    protected function createLineRequest(
        int $device,
        string $consumer,
        GPIOLineConfig $line_config,
        bool $has_nonblocking_input = false,
    ): GPIOLineRequest {
        $req_config = gpiod_request_config_new();
        gpiod_request_config_set_consumer($req_config, $consumer);

        $chip = gpiod_chip_open("/dev/gpiochip{$device}");
        $line_request = gpiod_chip_request_lines($chip, $req_config, $line_config);

        if (is_null($line_request)) {
            gpiod_chip_close($chip);

            throw new GPIOException("Could not request line {$this->pin} on /dev/gpiochip{$device}");
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

        return $line_request;
    }
}
