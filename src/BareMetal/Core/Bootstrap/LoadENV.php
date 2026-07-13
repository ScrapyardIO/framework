<?php

namespace BareMetal\Core\Bootstrap;

use Dotenv\Dotenv;
use Illuminate\Support\Env;
use Dotenv\Exception\InvalidFileException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use BareMetal\Contracts\Core\Application as ScrapyardAppInterface;

class LoadENV
{
    /**
     * Bootstrap the given application.
     */
    public function bootstrap(ScrapyardAppInterface $app): void
    {
        if ($app->configurationIsCached()) {
            return;
        }

        $this->checkForSpecificEnvironmentFile($app);

        try {
            $this->createDotenv($app)->safeLoad();
        } catch (InvalidFileException $e) {
            $this->writeErrorAndDie($e);
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     */
    protected function checkForSpecificEnvironmentFile(ScrapyardAppInterface $app): void
    {
        if ($app->runningInConsole() &&
            ($input = new ArgvInput)->hasParameterOption('--env') &&
            $this->setEnvironmentFilePath($app, $app->environmentFile().'.'.$input->getParameterOption('--env'))) {
            return;
        }

        $environment = Env::get('APP_ENV');

        if (! $environment) {
            return;
        }

        $this->setEnvironmentFilePath(
            $app, $app->environmentFile().'.'.$environment
        );
    }

    /**
     * Load a custom environment file.
     */
    protected function setEnvironmentFilePath(ScrapyardAppInterface $app, string $file): bool
    {
        if (is_file($app->environmentPath().'/'.$file)) {
            $app->loadEnvironmentFrom($file);

            return true;
        }

        return false;
    }

    /**
     * Create a Dotenv instance.
     */
    protected function createDotenv(ScrapyardAppInterface $app): Dotenv
    {
        return Dotenv::create(
            Env::getRepository(),
            $app->environmentPath(),
            $app->environmentFile()
        );
    }

    /**
     * Write the error information to the screen and exit.
     */
    protected function writeErrorAndDie(InvalidFileException $e): never
    {
        $output = (new ConsoleOutput)->getErrorOutput();

        $output->writeln('The environment file is invalid!');
        $output->writeln($e->getMessage());

        http_response_code(500);

        exit(1);
    }
}
