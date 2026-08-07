<?php

namespace Tests\Redis;

use Fabricate\Config\Repository;
use Fabricate\Contracts\Redis\Factory;
use Fabricate\Core\Machine;
use Fabricate\Redis\RedisManager;
use Fabricate\Redis\RedisServiceProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class RedisServiceProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scrapyard-redis-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
            $this->basePath.'/storage',
        ]);
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testProviderRegistersRedisBindings(): void
    {
        $app = new Machine($this->basePath);
        $app->instance('config', new Repository([
            'redis' => [
                'client' => 'phpredis',
                'options' => [
                    'prefix' => 'scrapyard-test-',
                ],
                'default' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'database' => 0,
                ],
            ],
        ]));

        $provider = new RedisServiceProvider($app);
        $provider->register();

        $this->assertTrue($app->bound('redis'));
        $this->assertTrue($app->bound('redis.connection'));
        $this->assertSame(['redis', 'redis.connection'], $provider->provides());

        $redis = $app->make('redis');

        $this->assertInstanceOf(RedisManager::class, $redis);
        $this->assertInstanceOf(Factory::class, $redis);
    }

    public function testProviderDefaultsClientWhenConfigMissing(): void
    {
        $app = new Machine($this->basePath);

        $provider = new RedisServiceProvider($app);
        $provider->register();

        $redis = $app->make('redis');

        $this->assertInstanceOf(RedisManager::class, $redis);
    }
}
