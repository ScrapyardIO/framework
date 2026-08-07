<?php

namespace Fabricate\Hashing;

use Error;
use Fabricate\Contracts\Hashing\Hasher as HasherContract;
use RuntimeException;

class ArgonHasher extends AbstractHasher implements HasherContract
{
    protected int $memory = 1024;

    protected int $time = 2;

    protected int $threads = 2;

    protected bool $verifyAlgorithm = false;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->time = $options['time'] ?? $this->time;
        $this->memory = $options['memory'] ?? $this->memory;
        $this->threads = $this->threads($options);
        $this->verifyAlgorithm = $options['verify'] ?? $this->verifyAlgorithm;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws \RuntimeException
     */
    public function make(#[\SensitiveParameter] $value, array $options = [])
    {
        try {
            $hash = password_hash($value, $this->algorithm(), [
                'memory_cost' => $this->memory($options),
                'time_cost' => $this->time($options),
                'threads' => $this->threads($options),
            ]);
        } catch (Error) {
            throw new RuntimeException('Argon2 hashing not supported.');
        }

        return $hash;
    }

    protected function algorithm()
    {
        return PASSWORD_ARGON2I;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws \RuntimeException
     */
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = [])
    {
        if (is_null($hashedValue) || (string) $hashedValue === '') {
            return false;
        }

        if ($this->verifyAlgorithm && ! $this->isUsingCorrectAlgorithm($hashedValue)) {
            throw new RuntimeException('This hash does not use the Argon2i algorithm.');
        }

        return parent::check($value, $hashedValue, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        return password_needs_rehash($hashedValue, $this->algorithm(), [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ]);
    }

    /**
     * @internal
     */
    public function verifyConfiguration($value)
    {
        return $this->isUsingCorrectAlgorithm($value) && $this->isUsingValidOptions($value);
    }

    protected function isUsingCorrectAlgorithm($hashedValue)
    {
        return $this->info($hashedValue)['algoName'] === 'argon2i';
    }

    protected function isUsingValidOptions($hashedValue)
    {
        ['options' => $options] = $this->info($hashedValue);

        if (
            ! is_int($options['memory_cost'] ?? null) ||
            ! is_int($options['time_cost'] ?? null) ||
            ! is_int($options['threads'] ?? null)
        ) {
            return false;
        }

        if (
            $options['memory_cost'] > $this->memory ||
            $options['time_cost'] > $this->time ||
            $options['threads'] > $this->threads
        ) {
            return false;
        }

        return true;
    }

    public function setMemory(int $memory)
    {
        $this->memory = $memory;

        return $this;
    }

    public function setTime(int $time)
    {
        $this->time = $time;

        return $this;
    }

    public function setThreads(int $threads)
    {
        $this->threads = $threads;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function memory(array $options)
    {
        return $options['memory'] ?? $this->memory;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function time(array $options)
    {
        return $options['time'] ?? $this->time;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function threads(array $options)
    {
        if (defined('PASSWORD_ARGON2_PROVIDER') && PASSWORD_ARGON2_PROVIDER === 'sodium') {
            return 1;
        }

        return $options['threads'] ?? $this->threads;
    }
}
