<?php

namespace BareMetal\Repositories;

use BareMetal\Exceptions\CircuitRepoException;
use BareMetal\IntegratedCircuit;
use Exception;

abstract class IntegratedCircuitRepository
{
    protected array $config = [];

    private static ?IntegratedCircuitRepository $instance = null;

    protected function loadConfig(): array
    {
        $base_path = getenv('SCRAPYARD_CONFIG_PATH');

        // @todo -  validate $basePath, throw if missing/invalid ...

        $config_file = rtrim($base_path, '/').'/scrapyard-io.php';

        // @todo -  validate file exists, throw if not ...

        return require $config_file;
    }

    public function hasCircuit(string $circuit_name): bool
    {
        return isset($this->config['boards'][$circuit_name]);
    }

    public function getCircuit(string $circuit_name): IntegratedCircuit|false
    {
        $circuit_def = $this->config['boards'][$circuit_name];

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

    public function __clone(): void {}

    public function __wakeup(): void {}

    public static function circuit(string $circuit_name): IntegratedCircuit
    {
        $repo = static::getInstance();

        if ($repo->hasCircuit($circuit_name)) {
            return $repo->getCircuit($circuit_name);
        }

        throw CircuitRepoException::integratedCircuitNotRegistered($circuit_name);
    }

    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }
}
