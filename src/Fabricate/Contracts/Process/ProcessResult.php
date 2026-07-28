<?php

namespace Fabricate\Contracts\Process;

interface ProcessResult
{
    /**
     * Get the original command executed by the process.
     *
     * @return string
     */
    public function command(): string;

    /**
     * Determine if the process was successful.
     *
     * @return bool
     */
    public function successful(): bool;

    /**
     * Determine if the process failed.
     *
     * @return bool
     */
    public function failed(): bool;

    /**
     * Get the exit code of the process.
     *
     * @return int|null
     */
    public function exitCode(): ?int;

    /**
     * Get the standard output of the process.
     *
     * @return string
     */
    public function output(): string;

    /**
     * Determine if the output contains the given string.
     *
     * @param  string  $output
     * @return bool
     */
    public function seeInOutput(string $output): bool;

    /**
     * Get the error output of the process.
     *
     * @return string
     */
    public function errorOutput(): string;

    /**
     * Determine if the error output contains the given string.
     *
     * @param  string  $output
     * @return bool
     */
    public function seeInErrorOutput(string $output): bool;

    /**
     * Throw an exception if the process failed.
     *
     * @param  callable|null  $callback
     * @return $this
     */
    public function throw(?callable $callback = null): static;

    /**
     * Throw an exception if the process failed and the given condition is true.
     *
     * @param  bool  $condition
     * @param  callable|null  $callback
     * @return $this
     */
    public function throwIf(bool $condition, ?callable $callback = null): static;
}
