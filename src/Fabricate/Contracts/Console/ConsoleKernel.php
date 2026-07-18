<?php

namespace Fabricate\Contracts\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleKernel
{
    /**
     * Bootstrap the application for artisan commands.
     *
     * @return void
     */
    public function bootstrap(): void;

    /**
     * Handle an incoming console command.
     *
     * @param InputInterface $input
     * @param OutputInterface|null $output
     * @return int
     */
    public function handle(InputInterface $input, ?OutputInterface $output = null): int;

    /**
     * Run an Artisan console command by name.
     *
     * @param string $command
     * @param  array  $parameters
     * @param OutputInterface|null $outputBuffer
     * @return int
     */
    public function call(string $command, array $parameters = [], ?OutputInterface $outputBuffer = null): int;

    /**
     * Get every command registered with the console.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string;

    /**
     * Terminate the application.
     *
     * @param InputInterface $input
     * @param int $status
     * @return void
     */
    public function terminate(InputInterface $input, int $status): void;
}
