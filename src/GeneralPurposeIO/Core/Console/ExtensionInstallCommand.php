<?php

namespace GeneralPurposeIO\Core\Console;

use GeneralPurposeIO\Core\Extensions\ExtensionsFlow;
use Symfony\Component\Console\Attribute\AsCommand;
use Voyager\Console\Command;
use Voyager\Workflows\SharedBag;

#[AsCommand(name: 'scrapyard:ext')]
class ExtensionInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected ?string $signature = 'scrapyard:ext {ext? : The extension to install: posi or ftdi. Omit it to choose from the list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Install ext-posi and ext-ftdi, the ScrapyardIO PHP extensions, through PIE';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $shared = new SharedBag;
        $shared->interactive = $this->input->isInteractive() && defined('STDIN') && stream_isatty(STDIN);
        $shared->only = $this->argument('ext');
        $shared->output = fn (string $type, string $buffer) => $this->output->write($buffer);

        new ExtensionsFlow()->run($shared);

        if (isset($shared->extension_results)) {
            $failed = false;
            foreach ($shared->extension_results as $extension => $outcome) {
                $this->components->twoColumnDetail($extension, $outcome);
                $failed = $failed || $outcome !== 'installed';
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $note = $shared->extensions_note;

        if (str_starts_with($note, 'Nothing to install') || $note === 'None selected.') {
            $this->components->info($note);

            return self::SUCCESS;
        }

        $this->components->error($note);

        return self::FAILURE;
    }
}
