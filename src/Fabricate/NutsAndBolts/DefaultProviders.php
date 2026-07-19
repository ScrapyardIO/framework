<?php

namespace Fabricate\NutsAndBolts;

use Fabricate\Gfx\RenderingServiceProvider;

class DefaultProviders
{
    /**
     * The current providers.
     *
     * @var array<class-string>
     */
    protected array $providers;

    /**
     * Create a new default provider collection.
     *
     * @param  array<class-string>|null  $providers
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [
            //\Fabricate\Auth\AuthServiceProvider::class,
            //\Fabricate\Broadcasting\BroadcastServiceProvider::class,
            \Fabricate\Bus\BusServiceProvider::class,
            \Fabricate\Cache\CacheServiceProvider::class,
            \Fabricate\Core\Providers\ConsoleSupportServiceProvider::class,
            //\Fabricate\Concurrency\ConcurrencyServiceProvider::class,
            //\Fabricate\Cookie\CookieServiceProvider::class,
            //\Fabricate\Database\DatabaseServiceProvider::class,
            \Fabricate\Displays\DisplaysServiceProvider::class,
            //\Fabricate\Encryption\EncryptionServiceProvider::class,
            \Fabricate\Filesystem\FilesystemServiceProvider::class,
            //\Fabricate\Foundation\Providers\FoundationServiceProvider::class,
            \Fabricate\Framebuffers\FramebufferServiceProvider::class,
            \GeneralPurposeIO\Common\GPIOServiceProvider::class,
            //\Fabricate\Hashing\HashServiceProvider::class,
            //\Fabricate\Mail\MailServiceProvider::class,
            //\Fabricate\Notifications\NotificationServiceProvider::class,
            //\Fabricate\Pagination\PaginationServiceProvider::class,
            //\Fabricate\Auth\Passwords\PasswordResetServiceProvider::class,
            //\Fabricate\Pipeline\PipelineServiceProvider::class,
            //\Fabricate\Queue\QueueServiceProvider::class,
            \Fabricate\Redis\RedisServiceProvider::class,
            \Fabricate\Gfx\RenderingServiceProvider::class,
            //\Fabricate\Session\SessionServiceProvider::class,
            \Fabricate\Actuation\ActuationServiceProvider::class,
            \Fabricate\Sensors\SensorServiceProvider::class,
            //\Fabricate\Translation\TranslationServiceProvider::class,
            //\Fabricate\Validation\ValidationServiceProvider::class,
            //\Fabricate\View\ViewServiceProvider::class,

        ];
    }

    /**
     * Merge the given providers into the provider collection.
     *
     * @param  array<class-string>  $providers
     * @return static
     */
    public function merge(array $providers)
    {
        $this->providers = array_merge($this->providers, $providers);

        return new static($this->providers);
    }

    /**
     * Replace the given providers with other providers.
     *
     * @param  array<class-string, class-string>  $replacements
     * @return static
     */
    public function replace(array $replacements)
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
     *
     * @param  array<class-string>  $providers
     * @return static
     */
    public function except(array $providers)
    {
        return new static((new Collection($this->providers))
            ->reject(fn ($p) => in_array($p, $providers))
            ->values()
            ->toArray());
    }

    /**
     * Convert the provider collection to an array.
     *
     * @return array<class-string>
     */
    public function toArray()
    {
        return $this->providers;
    }
}
