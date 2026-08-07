<?php

namespace Fabricate\NutsAndBolts;

use Fabricate\NutsAndBolts\Contracts\Htmlable;
use JsonSerializable;
use SensitiveParameter;
use Stringable;

class Uri implements Htmlable, JsonSerializable, Stringable
{
    public function __construct(protected string $uri = '') {}

    public static function of(Stringable|string $uri = ''): static { return new static((string) $uri); }
    public function scheme(): ?string { return parse_url($this->uri, PHP_URL_SCHEME) ?: null; }
    public function user(bool $withPassword = false): ?string
    {
        $user = parse_url($this->uri, PHP_URL_USER);
        if (! $withPassword || is_null($user)) return $user ?: null;
        $password = parse_url($this->uri, PHP_URL_PASS);
        return is_null($password) ? $user : $user.':'.$password;
    }
    public function password(): ?string { return parse_url($this->uri, PHP_URL_PASS) ?: null; }
    public function host(): ?string { return parse_url($this->uri, PHP_URL_HOST) ?: null; }
    public function port(): ?int { return parse_url($this->uri, PHP_URL_PORT) ?: null; }
    public function path(): string { $path = trim((string) parse_url($this->uri, PHP_URL_PATH), '/'); return $path === '' ? '/' : $path; }
    public function fragment(): ?string { return parse_url($this->uri, PHP_URL_FRAGMENT) ?: null; }
    public function withScheme(Stringable|string $scheme): static { return $this->replaceComponent('scheme', (string) $scheme); }
    public function withUser(Stringable|string|null $user, #[SensitiveParameter] Stringable|string|null $password = null): static
    { return $this->replaceComponent('user', is_null($user) ? null : (string) $user, is_null($password) ? null : (string) $password); }
    public function withHost(Stringable|string $host): static { return $this->replaceComponent('host', (string) $host); }
    public function withPort(?int $port): static { return $this->replaceComponent('port', $port); }
    public function withPath(Stringable|string $path): static { return $this->replaceComponent('path', '/'.ltrim((string) $path, '/')); }
    public function withFragment(string $fragment): static { return $this->replaceComponent('fragment', $fragment); }
    public function value(): string { return $this->uri; }
    public function toHtml(): string { return $this->uri; }
    public function isEmpty(): bool { return trim($this->uri) === ''; }
    public function jsonSerialize(): string { return $this->uri; }
    public function __toString(): string { return $this->uri; }

    protected function replaceComponent(string $key, mixed $value, ?string $password = null): static
    {
        $parts = parse_url($this->uri) ?: [];
        if (is_null($value)) unset($parts[$key]); else $parts[$key] = $value;
        if ($key === 'user') { if (is_null($password)) unset($parts['pass']); else $parts['pass'] = $password; }
        $authority = '';
        if (isset($parts['host'])) {
            $credentials = isset($parts['user']) ? rawurlencode((string) $parts['user']).(isset($parts['pass']) ? ':'.rawurlencode((string) $parts['pass']) : '').'@' : '';
            $authority = '//'.$credentials.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        }
        return new static((isset($parts['scheme']) ? $parts['scheme'].':' : '').$authority.($parts['path'] ?? '').(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : ''));
    }
}
