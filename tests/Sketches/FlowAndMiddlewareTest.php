<?php

use Fabricate\Contracts\Sketches\SketchExitStatus;
use Fabricate\Pipeline\Pipeline;
use Fabricate\Sketches\Flow\AsyncFlow;
use Fabricate\Sketches\Flow\AsyncNode;
use Fabricate\Sketches\Flow\Flow;
use Fabricate\Sketches\Flow\Node;
use Fabricate\Sketches\Middleware\DispatchSketch;
use Fabricate\Sketches\SketchRunContext;
use Fabricate\Sketches\SketchRunner;
use Tests\Sketches\Fixtures\CountingSketch;
use Tests\Sketches\Fixtures\RecordingMiddleware;

test('flow routes with self-loop until stop action', function () {
    $shared = ['n' => 0];

    $node = new class extends Node
    {
        public function prep(mixed &$shared): mixed
        {
            $shared['n'] = ($shared['n'] ?? 0) + 1;

            return $shared['n'];
        }

        public function exec(mixed $prepRes): mixed
        {
            return $prepRes >= 3 ? 'stop' : 'continue';
        }

        public function post(mixed &$shared, mixed $prepRes, mixed $execRes): mixed
        {
            return $execRes;
        }
    };

    $node->next($node, 'continue');

    $flow = new Flow($node);
    $flow->run($shared);

    expect($shared['n'])->toBe(3);
});

test('dispatch sketch runs middleware around flow runner', function () {
    RecordingMiddleware::$calls = [];

    $sketch = new CountingSketch(1);
    $runner = new SketchRunner;
    $pipeline = new Pipeline;
    $context = new SketchRunContext(
        name: 'counting',
        sketch: $sketch,
        runner: $runner,
    );

    $status = (new DispatchSketch($pipeline, $runner, [new RecordingMiddleware]))->run($context);

    expect($status)->toBe(SketchExitStatus::SUCCESS->value)
        ->and(RecordingMiddleware::$calls)->toBe(['before:counting', 'after:counting'])
        ->and($sketch->calls)->toBe(['boot', 'loop', 'shutdown']);
});

test('async flow runs async node via fiber concurrency', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-async-flow-'.uniqid();
    mkdir($basePath.'/config', 0777, true);

    file_put_contents($basePath.'/config/concurrency.php', "<?php\nreturn ['default' => 'fiber'];\n");
    file_put_contents($basePath.'/config/sketches.php', "<?php\nreturn ['concurrency' => 'fiber', 'load' => [], 'middleware' => []];\n");

    try {
        $app = new \Fabricate\Core\Machine($basePath);
        $app->bootstrapWith([
            \Fabricate\Core\Bootstrap\LoadConfiguration::class,
            \Fabricate\Core\Bootstrap\RegisterProviders::class,
            \Fabricate\Core\Bootstrap\BootProviders::class,
        ]);

        $shared = ['value' => 0];

        $node = new class extends AsyncNode
        {
            public function prepAsync(mixed &$shared): mixed
            {
                return $shared;
            }

            public function execAsync(mixed $prepRes): mixed
            {
                return ($prepRes['value'] ?? 0) + 5;
            }

            public function postAsync(mixed &$shared, mixed $prepRes, mixed $execRes): mixed
            {
                $shared['value'] = $execRes;

                return 'done';
            }
        };

        $flow = new AsyncFlow($node);
        $flow->run($shared);

        expect($shared['value'])->toBe(5);
    } finally {
        destroyTempMachinePath($basePath);
    }
});
