<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\WindowFactory as WindowFactoryContract;
use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Displays\DisplayComponent;
use Fabricate\Contracts\Displays\DisplayComponent as DisplayComponentInterface;

class DisplaysServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->machine->singleton('window', fn ($app) => new WindowFactoryManager($app));
        $this->machine->singleton('display', fn ($app) => new DisplayFactory());

    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'window',
            WindowFactoryManager::class,
            WindowFactoryContract::class,
            'display',
            DisplayFactory::class,
        ];
    }
}
