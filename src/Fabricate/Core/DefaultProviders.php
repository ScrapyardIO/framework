<?php

namespace Fabricate\Core;

use Fabricate\NutsAndBolts\Collection;

class DefaultProviders
{
    /**
     * The current providers.
     *
     * @var array<class-string>
     */
    protected array $providers;

    /**
     * Get the default providers for a ScrapyardIO application.
     *
     * Core owns this list (composition root) — not NutsAndBolts\ServiceProvider.
     *
     * @return static
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Create a new default provider collection.
     *
     * @param  array<class-string>|null  $providers
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [
            //\Fabricate\Broadcasting\BroadcastServiceProvider::class,
            \Fabricate\Core\Providers\BusServiceProvider::class,
            \Fabricate\Core\Providers\CacheServiceProvider::class,
            \Fabricate\Core\Providers\ConcurrencyServiceProvider::class,
            \Fabricate\Core\Providers\ConsoleSupportServiceProvider::class,
            \Fabricate\Core\Providers\DatabaseServiceProvider::class,
            \Fabricate\Core\Providers\MigrationServiceProvider::class,
            \Fabricate\Core\Providers\EncryptionServiceProvider::class,
            \Fabricate\Core\Providers\FilesystemServiceProvider::class,
            \Fabricate\Core\Providers\LogServiceProvider::class,
            \Fabricate\Core\Providers\CoreServiceProvider::class,
            \Fabricate\Core\Providers\HashServiceProvider::class,
            \Fabricate\Core\Providers\HttpServiceProvider::class,
            //\Fabricate\Mail\MailServiceProvider::class,
            //\Fabricate\Notifications\NotificationServiceProvider::class,
            //\Fabricate\Pagination\PaginationServiceProvider::class,
            //\Fabricate\Auth\Passwords\PasswordResetServiceProvider::class,
            \Fabricate\Core\Providers\PipelineServiceProvider::class,
            \Fabricate\Core\Providers\ProcessServiceProvider::class,
            \Fabricate\Core\Providers\QueueServiceProvider::class,
            \Fabricate\Core\Providers\RedisServiceProvider::class,
            \Fabricate\Core\Providers\TranslationServiceProvider::class,
            \Fabricate\Core\Providers\ValidationServiceProvider::class,

            //\Fabricate\Framebuffers\FramebufferServiceProvider::class,
            //\Fabricate\Rendering\RenderingServiceProvider::class,
            //\Fabricate\Fonts\FontsServiceProvider::class,
            //\Fabricate\Core\Providers\VisualServiceProvider::class,
            //\Fabricate\Circuits\CircuitsServiceProvider::class,
            //\Fabricate\Sensors\SensorsServiceProvider::class,
            //\Fabricate\Actuation\ActuationServiceProvider::class,
            //\Fabricate\Displays\DisplaysServiceProvider::class,
            \Fabricate\Sketches\SketchesServiceProvider::class,
        ];
    }

    /**
     * Merge the given providers into the provider collection.
     *
     * @param  array<class-string>  $providers
     * @return static
     */
    public function merge(array $providers): static
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
    public function replace(array $replacements): static
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
    public function except(array $providers): static
    {
        return new static(new Collection($this->providers)
            ->reject(fn ($p) => in_array($p, $providers))
            ->values()
            ->toArray());
    }

    /**
     * Convert the provider collection to an array.
     *
     * @return array<class-string>
     */
    public function toArray(): array
    {
        return $this->providers;
    }
}
