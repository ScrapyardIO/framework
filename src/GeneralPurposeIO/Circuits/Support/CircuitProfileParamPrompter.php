<?php

namespace GeneralPurposeIO\Circuits\Support;

use Voyager\Console\Command;
use GeneralPurposeIO\Contracts\Circuits\CircuitTransport;
use GeneralPurposeIO\Contracts\Circuits\Attributes\Pinout;

/**
 * Asks driver / device / pin questions from a #[Pinout] channel map
 * and returns named factory params (plus boot_now).
 */
final class CircuitProfileParamPrompter
{
    /**
     * @param  array<string, string|list<string>>|null  $pinout
     * @return array<string, mixed>
     */
    public static function prompt(Command $command, ?array $pinout, int $optionIndex = 0): array
    {
        $params = ['boot_now' => true];

        if (is_null($pinout) || $pinout === []) {
            return $params;
        }

        $attribute = new Pinout($pinout);
        $channels = $attribute->channels(0);

        /** @var array{adapter: ?string, device: string|int|null} $lastBus */
        $lastBus = ['adapter' => null, 'device' => null];

        foreach ($channels as $channel) {
            $transport = $channel['transport'];
            $label = $channel['label'];
            $roles = $channel['roles'];

            $adapterParam = is_null($transport) ? 'adapter' : $transport->adapterParam();
            $deviceParam = is_null($transport) ? 'device' : $transport->deviceParam();

            $defaultAdapter = $lastBus['adapter'] ?? 'usb';
            $adapter = $command->choice(
                "{$label} adapter (driver)",
                ['usb', 'posix'],
                $defaultAdapter,
            );
            $params[$adapterParam] = $adapter;

            $defaultDevice = ! is_null($lastBus['device'])
                ? (string) $lastBus['device']
                : ($adapter === 'posix' ? '1' : 'ft232h');

            $deviceAnswer = (string) $command->ask("{$label} device", $defaultDevice);
            $device = self::coerceDevice($deviceAnswer, $adapter);
            $params[$deviceParam] = $device;

            $lastBus = ['adapter' => $adapter, 'device' => $device];

            foreach ($roles as $role) {
                $normalized = strtolower($role);

                if (in_array($normalized, ['device', 'adapter', 'driver'], true)) {
                    continue;
                }

                if (in_array($normalized, ['chip_select', 'chipselect', 'cs'], true)) {
                    $params['chip_select'] = (int) $command->ask('SPI chip select', '0');

                    continue;
                }

                if ($normalized === 'slave') {
                    $slave = (string) $command->ask('I2C slave address (e.g. 0x3C or 60)', '0x3C');
                    $params['slave'] = self::coerceIntish($slave);

                    continue;
                }

                $param = str_ends_with($normalized, '_pin') ? $normalized : "{$normalized}_pin";
                $params[$param] = (int) $command->ask(
                    "{$label} {$role} pin",
                    $normalized === 'dc' ? '1' : ($normalized === 'rst' ? '2' : '0'),
                );
            }
        }

        return $params;
    }

    protected static function coerceDevice(string $value, string $adapter): string|int
    {
        if ($adapter === 'posix' && ctype_digit($value)) {
            return (int) $value;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        return $value;
    }

    protected static function coerceIntish(string $value): int
    {
        $trimmed = trim($value);

        if (str_starts_with(strtolower($trimmed), '0x')) {
            return (int) hexdec($trimmed);
        }

        return (int) $trimmed;
    }
}
