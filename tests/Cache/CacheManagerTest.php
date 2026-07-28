<?php

namespace DeptOfScrapyardRobotics\Tests\Cache;

use Fabricate\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CacheManagerTest extends TestCase
{
    public function testTheCacheManagerExposesAStoreApi(): void
    {
        $reflection = new ReflectionClass(CacheManager::class);

        $this->assertTrue($reflection->isInstantiable());
        $this->assertTrue($reflection->hasMethod('store'));
        $this->assertTrue($reflection->getMethod('store')->isPublic());
    }
}
