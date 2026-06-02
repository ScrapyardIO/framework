<?php

namespace Waveforms\Carriers\GPIO\API\Native\Factory;

use Exception;
use Microscrap\Bindings\GPIO\DataObjects\GPIOChip;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;
use Microscrap\Bindings\GPIO\Enums\GPIOV2LineFlag;
use Microscrap\Bindings\GPIO\Enums\LineDirection;
use Microscrap\Bindings\GPIO\Enums\LineEdge;
use Microscrap\Bindings\POSIX\Enums\FcntlCommand;
use Microscrap\Bindings\POSIX\Enums\FileControlFlag;
use Waveforms\Carriers\GPIO\API\Native\Exceptions\NativeGPIOException;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOBus;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOInput;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOOutput;
use Waveforms\Carriers\GPIO\Contracts\GPIOInput;
use Waveforms\Carriers\GPIO\Contracts\GPIOOutput;
use Waveforms\Carriers\GPIO\Exceptions\GPIOException;
use Waveforms\Carriers\GPIO\Factory\GPIOConnectionBuilder;

class NativeGPIOConnectionBuilder extends GPIOConnectionBuilder
{
    private string $device_path = '/dev/gpiochip';

    public ?int $gpio_chip = null;

    public ?GPIOChip $chip = null;

    public ?GPIOLineRequest $line_request = null;

    /**
     * @throws Exception
     */
    public function firstly(int|string $chip_device): static
    {
        if (is_string($chip_device)) {
            throw new Exception(static::class.' requires the gpiochip to be an int.');
        }

        return $this->gpiochip($chip_device);
    }

    public function gpiochip(int $chip): static
    {
        if (gpiod_check_gpiochip_device("{$this->device_path}{$chip}")) {
            $this->gpio_chip = $chip;

            return $this;
        }

        throw NativeGPIOException::chipDoesNotExist($chip);
    }

    public function addInput(GPIOInput $input): static
    {
        if ($input instanceof NativeGPIOInput) {
            $this->desired_gpio[$input->alias] = $input;

            return $this;
        }

        throw GPIOException::wrongGPIOPinType($input::class, 'native');
    }

    public function addOutput(GPIOOutput $output): static
    {
        if ($output instanceof NativeGPIOOutput) {
            $this->desired_gpio[$output->alias] = $output;

            return $this;
        }

        throw GPIOException::wrongGPIOPinType($output::class, 'native');
    }

    public function connection(): string
    {
        return 'native';
    }

    public function boot(): NativeGPIOBus
    {
        $gpio_chip = gpiod_chip_open("{$this->device_path}{$this->gpio_chip}");

        if (! is_null($gpio_chip)) {
            $line_config = gpiod_line_config_new();

            foreach ($this->desired_gpio as $line) {
                $settings = gpiod_line_settings_new();

                if (isset($line->flags['input'])) {
                    $direction = $line->flags['input'] === GPIOV2LineFlag::INPUT
                        ? LineDirection::Input
                        : LineDirection::Output;
                    gpiod_line_settings_set_direction($settings, $direction);
                }

                $hasRising = isset($line->flags['edge_rising']);
                $hasFalling = isset($line->flags['edge_falling']);
                $edge = null;

                if ($hasRising) {
                    $edge = LineEdge::Rising;
                }

                if ($hasFalling) {
                    $edge = is_null($edge) ? LineEdge::Falling : LineEdge::Both;
                }

                if ($edge) {
                    gpiod_line_settings_set_edge_detection($settings, $edge);
                }

                if (isset($line->flags['active_low'])) {
                    gpiod_line_settings_set_active_low($settings, true);
                }

                gpiod_line_config_add_line_settings($line_config, [$line->line], $settings);
            }

            $req_config = gpiod_request_config_new();
            gpiod_request_config_set_consumer($req_config, $this->request_consumer);

            $line_request = gpiod_chip_request_lines($gpio_chip, $req_config, $line_config);

            if ($line_request === null) {
                gpiod_chip_close($gpio_chip);
                throw NativeGPIOException::couldNotOpenGPIOChip($this->gpio_chip);
            }

            $hasNonblockingInput = ! empty(array_filter(
                $this->desired_gpio,
                fn ($line) => $line instanceof NativeGPIOInput && $line->nonblocking,
            ));

            if ($hasNonblockingInput) {
                fcntl($line_request->fd, FcntlCommand::F_GETFL->value, 0, $currentFlags);
                fcntl($line_request->fd, FcntlCommand::F_SETFL->value, $currentFlags | FileControlFlag::O_NONBLOCK->value, $ignored);
            }

            $this->chip = $gpio_chip;
            $this->line_request = $line_request;

            return new NativeGPIOBus($this->chip, $this->line_request, $this->desired_gpio);
        }

        throw NativeGPIOException::couldNotOpenGPIOChip($this->gpio_chip);
    }
}
