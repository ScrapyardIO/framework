<?php

namespace Fabricate\Hashing;

use Error;
use Fabricate\Contracts\Hashing\Hasher as HasherContract;
use InvalidArgumentException;
use RuntimeException;

class BcryptHasher extends AbstractHasher implements HasherContract
{
    protected int $rounds = 12;

    protected bool $verifyAlgorithm = false;

    protected ?int $limit = null;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->rounds = $options['rounds'] ?? $this->rounds;
        $this->verifyAlgorithm = $options['verify'] ?? $this->verifyAlgorithm;
        $this->limit = $options['limit'] ?? $this->limit;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function make(#[\SensitiveParameter] $value, array $options = [])
    {
        try {
            if ($this->limit && strlen($value) > $this->limit) {
                throw new InvalidArgumentException('Value is too long to hash. Value must be less than '.$this->limit.' bytes.');
            }

            $hash = password_hash($value, PASSWORD_BCRYPT, [
                'cost' => $this->cost($options),
            ]);
        } catch (Error) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }

        return $hash;
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
            throw new RuntimeException('This hash does not use the Bcrypt algorithm.');
        }

        return parent::check($value, $hashedValue, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => $this->cost($options),
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
        return $this->info($hashedValue)['algoName'] === 'bcrypt';
    }

    protected function isUsingValidOptions($hashedValue)
    {
        ['options' => $options] = $this->info($hashedValue);

        if (! is_int($options['cost'] ?? null)) {
            return false;
        }

        if ($options['cost'] > $this->rounds) {
            return false;
        }

        return true;
    }

    public function setRounds($rounds)
    {
        $this->rounds = (int) $rounds;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function cost(array $options = [])
    {
        return $options['rounds'] ?? $this->rounds;
    }
}
