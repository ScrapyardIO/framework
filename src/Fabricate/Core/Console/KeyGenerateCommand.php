<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Console\ConfirmableTrait;
use Fabricate\Console\Prohibitable;
use Fabricate\Encryption\Encrypter;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'key:generate')]
class KeyGenerateCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The name and signature of the console command.
     *
     * @var string|null
     */
    protected ?string $signature = 'key:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Set the application key';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle(): ?int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            $this->line('<comment>'.$key.'</comment>');

            return self::SUCCESS;
        }

        // Next, we will replace the application key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (! $this->setKeyInEnvironmentFile($key)) {
            return self::FAILURE;
        }

        $this->scrapyard_io['config']['machine.key'] = $key;

        $this->components->info('Application key set successfully.');

        return self::SUCCESS;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey(): string
    {
        return 'base64:'.base64_encode(
            Encrypter::generateKey($this->scrapyard_io['config']['machine.cipher'] ?? 'AES-256-CBC')
        );
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile(string $key): bool
    {
        $currentKey = (string) ($this->scrapyard_io['config']['machine.key'] ?? '');

        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        if (! $this->writeNewEnvironmentFileWith($key)) {
            return false;
        }

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return bool
     */
    protected function writeNewEnvironmentFileWith(string $key): bool
    {
        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'APP_KEY='.$key,
            $input = file_get_contents($this->scrapyard_io->environmentFilePath())
        );

        if ($replaced === $input || is_null($replaced)) {
            if (isset($_ENV['APP_KEY'])) {
                $this->components->error('Unable to set application key. APP_KEY is already present in the environment.');
            } else {
                $this->components->error('Unable to set application key. No APP_KEY variable was found in the .env file.');
            }

            return false;
        }

        file_put_contents($this->scrapyard_io->environmentFilePath(), $replaced);

        return true;
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern(): string
    {
        $escaped = preg_quote('='.($this->scrapyard_io['config']['machine.key'] ?? ''), '/');

        return "/^APP_KEY{$escaped}/m";
    }
}
