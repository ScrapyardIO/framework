<?php

namespace Fabricate\Contracts\Support;

use Exception;
use ScrapyardIO\NutsAndBolts\ScrapyardIOException;

trait BootScaffolding
{
    protected bool $booted = false;

    abstract protected function _boot(): void;

    /**
     * @return void
     * @throws Exception
     */
    public function boot(): void
    {
        if(!$this->hasBooted())
        {
            $this->_boot();
            $this->booted = true;
        }
    }

    public function hasBooted(): bool
    {
        return $this->booted;
    }
}
