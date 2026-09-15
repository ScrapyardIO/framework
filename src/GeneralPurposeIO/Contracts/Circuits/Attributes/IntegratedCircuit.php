<?php

namespace GeneralPurposeIO\Contracts\Circuits\Attributes;

use Attribute;

/**
 * Declares protocol options for an IC class.
 *
 * Each variadic entry is one provisioning option:
 * - string: single-bus option (`'I2C'`)
 * - list: co-required buses (`['SPI', 'DigitalIO']`)
 *
 * Examples:
 *   #[IntegratedCircuit('I2C', ['SPI', 'DigitalIO'])]
 *   #[IntegratedCircuit(['SPI', 'DigitalIO'])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
class IntegratedCircuit
{
    /**
     * Raw constructor entries (string or list of protocol names).
     *
     * @var list<string|list<string>>
     */
    public array $protocols;

    /**
     * @param  string|list<string>  ...$protocols
     */
    public function __construct(string|array ...$protocols)
    {
        $this->protocols = $protocols;
    }

    /**
     * Normalized options for CLI / config tooling.
     *
     * @return list<array{label: string, protocols: list<string>, factory: string}>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->protocols as $entry) {
            $group = is_array($entry)
                ? array_values(array_map('strval', $entry))
                : [(string) $entry];

            if ($group === []) {
                continue;
            }

            $options[] = [
                'label' => implode('+', $group),
                'protocols' => $group,
                'factory' => $this->factoryMethodName($group),
            ];
        }

        return $options;
    }

    /**
     * @param  list<string>  $group
     */
    protected function factoryMethodName(array $group): string
    {
        foreach ($group as $protocol) {
            $normalized = strtolower($protocol);

            if (in_array($normalized, ['digitalio', 'digital_io', 'gpio'], true)) {
                continue;
            }

            return $normalized;
        }

        return strtolower($group[0]);
    }
}
