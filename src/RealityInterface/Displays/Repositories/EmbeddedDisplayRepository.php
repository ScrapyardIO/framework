<?php

namespace RealityInterface\Displays\Repositories;

use BareMetal\Repositories\IntegratedCircuitRepository;
use Exception;
use RealityInterface\Displays\EmbeddedDisplay;
use RealityInterface\Displays\Exceptions\DisplayRepoException;

class EmbeddedDisplayRepository extends IntegratedCircuitRepository
{
    protected function __construct()
    {
        $this->config = $this->loadConfig();
    }

    public function hasDisplay(string $sensor_name): bool
    {
        return isset($this->config['displays'][$sensor_name]);
    }

    public function getDisplay(string $circuit_name): EmbeddedDisplay|false
    {
        $circuit_def = $this->config['displays'][$circuit_name];

        try {
            $class_name = $circuit_def['class_name'];
            $connection = $circuit_def['connection'];

            $circuit = $class_name::connection(...$connection);
            $startup = $circuit_def['startup'];
            foreach ($startup as $cmd => $args) {
                $circuit = $circuit->$cmd(...$args);
            }

            $results = $circuit->create();
        } catch (Exception $e) {
            $results = false;
        }

        return $results;
    }

    public static function display(string $display_name): EmbeddedDisplay
    {
        $repo = static::getInstance();
        if ($repo->hasDisplay($display_name)) {
            $display = $repo->getDisplay($display_name);

            if ($display instanceof EmbeddedDisplay) {
                return $display;
            }
        }

        throw DisplayRepoException::embeddedDisplayNotRegistered($display_name);
    }
}
