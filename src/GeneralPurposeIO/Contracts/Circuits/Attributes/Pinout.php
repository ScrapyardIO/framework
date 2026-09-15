<?php

namespace GeneralPurposeIO\Contracts\Circuits\Attributes;

use Attribute;
use GeneralPurposeIO\Contracts\Circuits\CircuitTransport;

/**
 * Declares bus + pin roles per protocol option (index-aligned with
 * {@see IntegratedCircuit} options).
 *
 * Each map key is a transport channel. Values are role labels:
 * - `device` — bus device id (adapter is always prompted for the channel)
 * - `chip_select` / `cs` — SPI chip select
 * - `slave` — I2C address
 * - other strings — GPIO roles mapped to `{role}_pin` (e.g. dc → dc_pin)
 *
 * Examples:
 *   #[Pinout(['I2C' => ['device', 'slave']], ['SPI' => ['device', 'chip_select'], 'DigitalIO' => ['dc', 'rst']])]
 *   #[Pinout(['SPI' => ['device', 'chip_select'], 'DigitalIO' => ['dc', 'rst']])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Pinout
{
    /**
     * @var list<array<string, string|list<string>>>
     */
    public array $pinout;

    /**
     * @param  array<string, string|list<string>>  ...$pinout
     */
    public function __construct(array ...$pinout)
    {
        $this->pinout = $pinout;
    }

    /**
     * @return array<string, string|list<string>>|null
     */
    public function forOptionIndex(int $index): ?array
    {
        return $this->pinout[$index] ?? null;
    }

    /**
     * Normalized channels for interactive profile scaffolding.
     *
     * @return list<array{transport: CircuitTransport, label: string, roles: list<string>}>
     */
    public function channels(int $optionIndex): array
    {
        $map = $this->forOptionIndex($optionIndex);

        if (is_null($map)) {
            return [];
        }

        $channels = [];

        foreach ($map as $protocol => $roles) {
            $transport = CircuitTransport::tryFromLabel((string) $protocol);
            $roleList = is_array($roles) ? array_values(array_map('strval', $roles)) : [(string) $roles];

            $channels[] = [
                'transport' => $transport,
                'label' => (string) $protocol,
                'roles' => $roleList === [] ? ['device'] : $roleList,
            ];
        }

        return $channels;
    }

    /**
     * @return list<string>
     */
    public function hintLines(int $optionIndex): array
    {
        $lines = [];

        foreach ($this->channels($optionIndex) as $channel) {
            $lines[] = $channel['label'].': '.implode(', ', $channel['roles']);
        }

        return $lines;
    }
}
