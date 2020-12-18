<?php

declare(strict_types=1);

namespace Azexsoft\Di;

use Azexsoft\Di\Contracts\DeferredServiceProviderInterface;
use Azexsoft\Di\Contracts\ServiceProviderInterface;
use Azexsoft\Di\Exception\CircularReferenceException;
use Azexsoft\Di\Exception\InvalidConfigException;
use Azexsoft\Di\Exception\NotFoundException;
use Closure;
use Exception;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    /**
     * @var array used to collect ids instantiated during build
     * to detect circular references
     */
    private array $building = [];

    private array $bindings = [];

    private array $instances = [];

    /**
     * Container constructor.
     *
     * @param array $bindings
     * @param string[] $providers
     *
     * @throws InvalidConfigException which can not resolve arguments or provider does not implements ServiceProviderInterface
     * @throws CircularReferenceException which circular reference detected while building
     */
    public function __construct(array $bindings = [], array $providers = [])
    {
        $this->bind(ContainerInterface::class, fn() => $this);
        $this->bind(Injector::class, new Injector($this));

        // Bindings register
        foreach ($bindings as $abstract => $concrete) {
            $this->bind($abstract, $concrete);
        }

        // Service providers register
        foreach ($providers as $provider) {
            $this->provide($provider);
        }
    }

    /**
     * Bind abstract classname to concrete through classname or closure.
     *
     * @param string $abstract Abstract classname
     * @param string|Closure|object $concrete Concrete classname or closure
     */
    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        unset($this->instances[$abstract]);
    }

    /**
     * Adds service provider to the container. Unless service provider is deferred
     * it would be immediately registered.
     *
     * @param string $provider provider classname
     *
     * @throws InvalidConfigException which can not resolve arguments or provider does not implements ServiceProviderInterface
     * @throws CircularReferenceException which circular reference detected while building
     */
    public function provide(string $provider): void
    {
        $provider = $this->build($provider);
        if (!$provider instanceof ServiceProviderInterface) {
            throw new InvalidConfigException(
                'Service provider should be an instance of ' . ServiceProviderInterface::class
            );
        }

        if ($provider instanceof DeferredServiceProviderInterface) {
            foreach ($provider->provides() as $id) {
                $this->bindings[$id] = $provider;
            }
        } else {
            $provider->register($this);
        }
    }

    /**
     * Build instance of abstract or concrete class with resolve arguments.
     *
     * @param string $abstract Abstract or concrete classname.
     * @param array $arguments Arguments which provides to class constructor.
     * @return object Instance of abstract classname.
     *
     * @throws InvalidConfigException which can not resolve arguments
     * @throws CircularReferenceException which circular reference detected while building
     */
    public function build(string $abstract, array $arguments = []): object
    {
        // Get concrete classname
        $concrete = $abstract;
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
        }

        // Register deferred service provider services
        if ($concrete instanceof ServiceProviderInterface) {
            unset($this->bindings[$abstract]);
            $concrete->register($this);
            return $this->build($abstract, $arguments);
        }

        // Check for circular
        if (isset($this->building[$abstract])) {
            throw new CircularReferenceException(
                sprintf(
                    'Circular reference to "%s" detected while building: %s',
                    $abstract,
                    implode(',', array_keys($this->building))
                )
            );
        }
        $this->building[$abstract] = 1;

        // Make instance of concrete implementation of abstract
        $instance = is_object($concrete)
            ? $concrete instanceof Closure
                ? $this->get(Injector::class)->invoke($concrete)
                : $concrete
            : $this->get(Injector::class)->build($concrete, $arguments);

        // Remove circular lock
        unset($this->building[$abstract]);

        return $this->instances[$abstract] = $instance;
    }

    public function get($id)
    {
        try {
            if (isset($this->instances[$id])) {
                return $this->instances[$id];
            }
            return $this->build($id);
        } catch (Exception $e) {
            if (!$this->has($id)) {
                throw new NotFoundException($id, $e->getCode(), $e);
            }
            throw $e;
        }
    }

    public function has($id)
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }
}
