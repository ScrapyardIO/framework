<?php

namespace Fabricate\Core\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Fabricate\Contracts\Core\Program;
use Fabricate\NutsAndBolts\Env;
use Laravel\Prompts\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;

class LoadEnvironmentVariables
{
    public function bootstrap(Program $program): void
    {
        if ($program->configurationIsCached()) {
            return;
        }

        $this->checkForSpecificEnvironmentFile($program);

        try {
            $this->createDotenv($program)->safeLoad();
        } catch (InvalidFileException $e) {
            $this->writeErrorAndDie($e);
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     *
     * @param Program $program
     * @return void
     */
    protected function checkForSpecificEnvironmentFile(Program $program): void
    {
        if ((!$program->runningInProduction()) &&
            ($input = new ArgvInput)->hasParameterOption('--env') &&
            $this->setEnvironmentFilePath($program, $program->environmentFile().'.'.$input->getParameterOption('--env'))) {
            return;
        }

        $environment = Env::get('APP_ENV');

        if (! $environment) {
            return;
        }

        $this->setEnvironmentFilePath(
            $program, $program->environmentFile().'.'.$environment
        );
    }

    /**
     * Load a custom environment file.
     *
     * @param Program $program
     * @param string $file
     * @return bool
     */
    protected function setEnvironmentFilePath(Program $program, string $file): bool
    {
        if (is_file($program->environmentPath().'/'.$file)) {
            $program->loadEnvironmentFrom($file);

            return true;
        }

        return false;
    }

    /**
     * Create a Dotenv instance.
     *
     * @param Program $program
     * @return Dotenv
     */
    protected function createDotenv(Program $program): Dotenv
    {
        return Dotenv::create(
            Env::getRepository(),
            $program->environmentPath(),
            $program->environmentFile()
        );
    }

    /**
     * Write the error information to the screen and exit.
     *
     * @param InvalidFileException $e
     * @return never
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