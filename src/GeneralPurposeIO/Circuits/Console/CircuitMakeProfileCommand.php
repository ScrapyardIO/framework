<?php

namespace GeneralPurposeIO\Circuits\Console;

use Voyager\Console\Command;
use GeneralPurposeIO\Circuits\CircuitRegistry;
use GeneralPurposeIO\Circuits\Console\Concerns\ScaffoldsCircuitProfiles;
use GeneralPurposeIO\Circuits\Enums\CircuitConsoleCommand;
use GeneralPurposeIO\Circuits\Support\CircuitAttributeInspector;
use GeneralPurposeIO\Contracts\Circuits\CircuitException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'circuit:make-profile')]
class CircuitMakeProfileCommand extends Command
{
    use ScaffoldsCircuitProfiles;

    protected ?string $signature = 'circuit:make-profile
                    {ic? : Catalog IC slug (e.g. st7789)}
                    {name? : Profile key to write into config/circuits.php}
                    {--protocol= : Protocol option label or factory name when non-interactive}';

    protected string $description = 'Scaffold a named circuit profile for any installed IC';

    public function handle(CircuitRegistry $registry): int
    {
        $circuits = $registry->listCircuits();

        if ($circuits === []) {
            $this->components->error('No ICs are registered. Install an IC package (e.g. dept-of-scrapyard-robotics/st77xx).');

            return self::FAILURE;
        }

        $ic = $this->argument('ic');
        if (is_null($ic) || $ic === '') {
            $ic = $this->choice('Which installed IC?', array_keys($circuits));
        }

        $ic = (string) $ic;

        if (! isset($circuits[$ic])) {
            $this->components->error("Circuit [{$ic}] is not registered.");

            return self::FAILURE;
        }

        $delegate = $registry->profileCommand($ic);

        if (! is_null($delegate) && $delegate !== CircuitConsoleCommand::MAKE_PROFILE->value) {
            return $this->call($delegate, array_filter([
                'ic' => $ic,
                'name' => $this->argument('name'),
                '--protocol' => $this->option('protocol'),
            ], static fn ($value) => ! is_null($value) && $value !== ''));
        }

        return $this->scaffoldGeneric($registry, $ic);
    }

    protected function scaffoldGeneric(CircuitRegistry $registry, string $ic): int
    {
        try {
            $class = $registry->resolveClass($ic);
            $options = CircuitAttributeInspector::protocolOptions($class);
        } catch (CircuitException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $selected = $this->resolveProtocolOption($options);
        if (is_null($selected)) {
            return self::FAILURE;
        }

        $name = $this->argument('name');
        if (is_null($name) || $name === '') {
            $name = $this->ask('Profile name', $ic);
        }

        return $this->writePromptedProfile($ic, (string) $name, $selected);
    }
}
