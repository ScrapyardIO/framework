<?php

namespace ScrapyardIO\NutsAndBolts;

use ScrpayardIO\NutsAndBolts\Collection;

class DefaultProviders
{
    /**
     * The current providers.
     */
    protected array $providers;

    /**
     * Create a new default provider collection.
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [
            //\BareMetal\Auth\AuthServiceProvider::class,
            //\BareMetal\Broadcasting\BroadcastServiceProvider::class,
            //\BareMetal\Bus\BusServiceProvider::class,
            //\BareMetal\Cache\CacheServiceProvider::class,
            //\BareMetal\Foundation\Providers\ConsoleSupportServiceProvider::class,
            //\BareMetal\Concurrency\ConcurrencyServiceProvider::class,
            //\BareMetal\Cookie\CookieServiceProvider::class,
            //\BareMetal\Database\DatabaseServiceProvider::class,
            //\BareMetal\Encryption\EncryptionServiceProvider::class,
            //\BareMetal\Filesystem\FilesystemServiceProvider::class,
            //\BareMetal\Foundation\Providers\FoundationServiceProvider::class,
            //\BareMetal\Hashing\HashServiceProvider::class,
            //\BareMetal\Mail\MailServiceProvider::class,
            //\BareMetal\Notifications\NotificationServiceProvider::class,
            //\BareMetal\Pagination\PaginationServiceProvider::class,
            //\BareMetal\Auth\Passwords\PasswordResetServiceProvider::class,
            //\BareMetal\Pipeline\PipelineServiceProvider::class,
            //\BareMetal\Queue\QueueServiceProvider::class,
            //\BareMetal\Redis\RedisServiceProvider::class,
            //\BareMetal\Session\SessionServiceProvider::class,
            //\BareMetal\Translation\TranslationServiceProvider::class,
            //\BareMetal\Validation\ValidationServiceProvider::class,
            //\BareMetal\View\ViewServiceProvider::class,
        ];
    }

    /**
     * Merge the given providers into the provider collection.
     */
    public function merge(array $providers): DefaultProviders
    {
        $this->providers = array_merge($this->providers, $providers);

        return new static($this->providers);
    }

    /**
     * Replace the given providers with other providers.
     */
    public function replace(array $replacements): DefaultProviders
    {
        $current = new Collection($this->providers);

        foreach ($replacements as $from => $to) {
            $key = $current->search($from);

            $current = is_int($key) ? $current->replace([$key => $to]) : $current;
        }

        return new static($current->values()->toArray());
    }

    /**
     * Disable the given providers.
     */
    public function except(array $providers): DefaultProviders
    {
        return new static((new Collection($this->providers))
            ->reject(fn ($p) => in_array($p, $providers))
            ->values()
            ->toArray());
    }

    /**
     * Convert the provider collection to an array.
     */
    public function toArray(): array
    {
        return $this->providers;
    }
}
