<?php

namespace GeneralPurposeIO\Core\Console;

use GeneralPurposeIO\Core\Modules\ModulesFlow;
use Symfony\Component\Console\Attribute\AsCommand;
use Voyager\Console\Command;
use Voyager\Workflows\SharedBag;

#[AsCommand(name: 'scrapyard:modules')]
class ModuleInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected ?string $signature = 'scrapyard:modules {module? : The module to install: gpio, i2c, spi, uart, mpsse, scrapyard-linux or scrapyard-usb. Omit it to choose from the list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Install the microscrap packages this machine\'s operating system and PHP extensions can run';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $shared = new SharedBag;
        $shared->interactive = $this->input->isInteractive() && defined('STDIN') && stream_isatty(STDIN);
        $shared->only = $this->argument('module');
        $shared->base_path = $this->venusian->basePath();
        $shared->output = fn (string $type, string $buffer) => $this->output->write($buffer);

        new ModulesFlow()->run($shared);

        if (isset($shared->module_results)) {
            $failed = false;
            foreach ($shared->module_results as $module => $outcome) {
                $this->components->twoColumnDetail($module, $outcome);
                $failed = $failed || $outcome !== 'installed';
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $note = $shared->modules_note;

        if (str_starts_with($note, 'Nothing to install') || $note === 'None selected.') {
            $this->components->info($note);

            return self::SUCCESS;
        }

        $this->components->error($note);

        return self::FAILURE;
    }
}
