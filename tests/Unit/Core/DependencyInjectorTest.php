<?php

namespace Tests\Unit\Core;

use Exception;
use PHPUnit\Framework\TestCase;
use App\Core\DependencyInjector;
use Tests\Unit\Core\FakeClasses\ClassWithDefault;
use Tests\Unit\Core\FakeClasses\MyService;
use Tests\Unit\Core\FakeClasses\ServiceConsumer;

class DependencyInjectorTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRegisterAndResolveBasic()
    {
        $injector = new DependencyInjector();

        $injector->register(MyService::class, fn() => new MyService());

        $instance = $injector->resolve(MyService::class);

        $this->assertInstanceOf(MyService::class, $instance);
    }

    /**
     * @throws Exception
     */
    public function testSingletonIsShared()
    {
        $injector = new DependencyInjector();

        $injector->register(MyService::class, fn() => new MyService(), true);

        $a = $injector->resolve(MyService::class);
        $b = $injector->resolve(MyService::class);

        $this->assertSame($a, $b); // Singleton : même instance
    }

    /**
     * @throws Exception
     */
    public function testConstructorInjectionByType()
    {
        $injector = new DependencyInjector();

        $injector->register(MyService::class, fn() => new MyService());
        $injector->register(ServiceConsumer::class, fn() => new ServiceConsumer($injector->resolve(MyService::class)));
        $consumer = $injector->resolve(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertInstanceOf(MyService::class, $consumer->service);
    }


    /**
     * @throws Exception
     */
    public function testConstructorInjectionWithArgumentOverride()
    {
        $injector = new DependencyInjector();

        $serviceMock = $this->createMock(MyService::class);

        $consumer = $injector->resolve(ServiceConsumer::class, 'default', [
            'service' => $serviceMock
        ]);

        $this->assertSame($serviceMock, $consumer->service);
    }

    /**
     * @throws Exception
     */
    public function testDependencyWithDefaultValueIsUsed()
    {
        $injector = new DependencyInjector();

        $instance = $injector->resolve(ClassWithDefault::class);
        $this->assertInstanceOf(ClassWithDefault::class, $instance);
        $this->assertSame('default', $instance->value);
    }

    public function testErrorOnMissingDependency()
    {
        $this->expectException(Exception::class);

        $injector = new DependencyInjector();
        $injector->resolve(ServiceConsumer::class); // MyService non enregistré
    }

    /**
     * @throws Exception
     */
    public function testInitializeDependenciesInitializesSingletons()
    {
        $injector = new DependencyInjector();

        $injector->register(MyService::class, fn() => new MyService(), true);
        $injector->initializeDependencies();

        $this->assertInstanceOf(MyService::class, $injector->singletons[MyService::class . 'default']);
    }
}
