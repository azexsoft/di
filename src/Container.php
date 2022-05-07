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
    protected $building = [];

    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @var array
     */
    protected $instances = [];

    /**
     * Container constructor.
     *
     * @param array $bindings
     * @param array<ServiceProviderInterface|class-string> $providers
     *
     * @throws InvalidConfigException which can not resolve arguments or provider does not implement ServiceProviderInterface
     * @throws CircularReferenceException which circular reference detected while building
     * @throws NotFoundException which not found
     */
    public function __construct(array $bindings = [], array $providers = [])
    {
        $this->bind(ContainerInterface::class, function() {
            return $this;
        });
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
     * @param class-string $abstract Abstract classname
     * @param class-string|object|array|Closure $concrete Concrete classname, instance, array definition or closure
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
     * @param ServiceProviderInterface|class-string $provider provider instance or classname
     *
     * @throws InvalidConfigException which can not resolve arguments or provider does not implement ServiceProviderInterface
     * @throws CircularReferenceException which circular reference detected while building
     * @throws NotFoundException which not found
     */
    public function provide($provider): void
    {
        if (is_string($provider)) {
            $provider = $this->build($provider);
        }

        if (!$provider instanceof ServiceProviderInterface) {
            throw new InvalidConfigException(
                'Service provider should be an instance of ' . ServiceProviderInterface::class
            );
        }

        if ($provider instanceof DeferredServiceProviderInterface) {
            foreach ($provider->provides() as $id) {
                $this->bindings[$id] = $provider;
                unset($this->instances[$id]);
            }
        } else {
            $provider->register($this);
        }
    }

    /**
     * Build instance of abstract or concrete class with resolve arguments.
     *
     * @template T
     *
     * @param class-string<T> $abstract Abstract or concrete classname.
     * @param array $arguments Arguments which provides to class constructor.
     * @return T Instance of abstract classname.
     *
     * @throws InvalidConfigException which can not resolve arguments
     * @throws CircularReferenceException which circular reference detected while building
     * @throws NotFoundException which not found
     */
    public function build(string $abstract, array $arguments = [])
    {
        // Get concrete classname
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // Get concrete binding if abstract binding is string
        if (is_string($concrete) && isset($this->bindings[$concrete])) {
            $concrete = $this->bindings[$concrete];
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
        if (is_object($concrete)) {
            $instance = $concrete instanceof Closure
                ? $this->get(Injector::class)->invoke($concrete)
                : $concrete;
        } else {
            $instance = $this->get(Injector::class)->build($concrete, $arguments);
        }

        // Remove circular lock
        unset($this->building[$abstract]);

        return $this->instances[$abstract] = $instance;
    }

    /**
     * Returns an instance by either interface name or alias.
     *
     * Same instance of the class will be returned each time this method is called.
     *
     * @template T
     * @param string|class-string<T> $id The interface or an alias name that was previously registered.
     * @return T|mixed
     *
     * @throws InvalidConfigException which can not resolve arguments
     * @throws CircularReferenceException which circular reference detected while building
     * @throws NotFoundException which not found
     */
    public function get($id)
    {
        try {
            return $this->instances[$id] ?? $this->build($id);
        } catch (Exception $e) {
            if (!$this->has($id)) {
                throw new NotFoundException($id, $e->getCode(), $e);
            }
            throw $e;
        }
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     *
     * @param class-string $id class name or interface name
     *
     * @return bool whether the container is able to provide instance of class specified.
     */
    public function has($id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }
}
