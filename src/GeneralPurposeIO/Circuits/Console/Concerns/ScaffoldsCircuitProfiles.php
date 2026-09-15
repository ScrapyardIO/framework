<?php

namespace GeneralPurposeIO\Circuits\Console\Concerns;

use GeneralPurposeIO\Circuits\Support\CircuitProfileParamPrompter;
use GeneralPurposeIO\Circuits\Support\CircuitProfileWriter;
use Throwable;

trait ScaffoldsCircuitProfiles
{
    /**
     * @param  list<array{label: string, protocols: list<string>, factory: string, pinout: array<string, string|list<string>>|null, pinout_hints: list<string>}>  $options
     * @return array{label: string, protocols: list<string>, factory: string, pinout: array<string, string|list<string>>|null, pinout_hints: list<string>}|null
     */
    protected function resolveProtocolOption(array $options): ?array
    {
        $protocolOpt = $this->option('protocol');

        if (is_string($protocolOpt) && $protocolOpt !== '') {
            foreach ($options as $option) {
                if (
                    strcasecmp($option['label'], $protocolOpt) === 0
                    || strcasecmp($option['factory'], $protocolOpt) === 0
                ) {
                    return $option;
                }
            }

            $this->components->error("Unknown protocol option [{$protocolOpt}].");

            return null;
        }

        if (count($options) === 1) {
            return $options[0];
        }

        $labels = array_map(static fn (array $option): string => $option['label'], $options);
        $choice = $this->choice('Which protocol option?', $labels);

        foreach ($options as $option) {
            if ($option['label'] === $choice) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param  array{label: string, factory: string, pinout: array<string, string|list<string>>|null}  $selected
     */
    protected function writePromptedProfile(string $ic, string $profileName, array $selected): int
    {
        $params = CircuitProfileParamPrompter::prompt($this, $selected['pinout']);
        $path = $this->scrapyard_io->configPath('circuits.php');

        try {
            CircuitProfileWriter::append(
                $path,
                $profileName,
                $ic,
                $selected['factory'],
                $params,
            );
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(
            "Wrote profile [{$profileName}] for IC [{$ic}] ({$selected['label']}) → {$path}"
        );
        $this->components->info("Use: Circuit::profile('{$profileName}')");

        return self::SUCCESS;
    }
}
