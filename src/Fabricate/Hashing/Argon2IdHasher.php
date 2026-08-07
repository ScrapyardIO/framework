<?php

namespace Fabricate\Hashing;

use RuntimeException;

class Argon2IdHasher extends ArgonHasher
{
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
            throw new RuntimeException('This hash does not use the Argon2id algorithm.');
        }

        return password_verify($value, $hashedValue);
    }

    protected function isUsingCorrectAlgorithm($hashedValue)
    {
        return $this->info($hashedValue)['algoName'] === 'argon2id';
    }

    protected function algorithm()
    {
        return PASSWORD_ARGON2ID;
    }
}
