<?php

namespace Fabricate\Validation;

use Exception;
use Fabricate\Contracts\Validation\UncompromisedVerifier;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Stringable;

/**
 * Have I Been Pwned range API verifier.
 *
 * Core does not register this by default (no HTTP client binding). When no
 * factory is supplied, {@see verify()} passes so Password rules do not hard-fail offline.
 */
class NotPwnedVerifier implements UncompromisedVerifier
{
    /**
     * @param  object|null  $factory  HTTP client factory with withHeaders(), timeout(), get().
     */
    public function __construct(
        protected ?object $factory = null,
        protected int $timeout = 30,
    ) {
    }

    public function verify($data): bool
    {
        if (is_null($this->factory)) {
            return true;
        }

        $value = $data['value'];
        $threshold = $data['threshold'];

        if (empty($value = (string) $value)) {
            return false;
        }

        [$hash, $hashPrefix] = $this->getHash($value);

        return ! $this->search($hashPrefix)
            ->contains(function ($line) use ($hash, $hashPrefix, $threshold) {
                [$hashSuffix, $count] = explode(':', $line);

                return $hashPrefix.$hashSuffix === $hash && $count > $threshold;
            });
    }

    protected function getHash($value): array
    {
        $hash = strtoupper(sha1((string) $value));

        return [$hash, substr($hash, 0, 5)];
    }

    protected function search($hashPrefix): Collection
    {
        try {
            $response = $this->factory->withHeaders([
                'Add-Padding' => true,
            ])->timeout($this->timeout)->get(
                'https://api.pwnedpasswords.com/range/'.$hashPrefix
            );
        } catch (Exception $e) {
            if (function_exists('report')) {
                report($e);
            }
        }

        $body = (isset($response) && $response->successful())
            ? $response->body()
            : '';

        return (new Stringable($body))->trim()->explode("\n")->filter(function ($line) {
            return str_contains($line, ':');
        });
    }
}
