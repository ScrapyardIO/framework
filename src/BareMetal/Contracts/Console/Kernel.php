<?php

namespace BareMetal\Contracts\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Kernel
{
    /**
     * Bootstrap the application for scrapyard-io commands.
     */
    public function bootstrap(): void;

    /**
     * Handle an incoming console command.
     */
    public function handle(InputInterface $input, ?OutputInterface $output = null): int;

    /**
     * Run an ScrapyardIO console command by name.
     */
    public function call(string $command, array $parameters = [], ?OutputInterface $outputBuffer = null): int;

    /**
     * Get every command registered with the console.
     */
    public function all(): array;

    /**
     * Get the output for the last run command.
     */
    public function output(): string;

    /**
     * Terminate the application.
     */
    public function terminate(InputInterface$input, int $status): void;
}
