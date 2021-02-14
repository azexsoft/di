<?php

declare(strict_types=1);

namespace Azexsoft\Di;

use Azexsoft\Di\Exception\InvalidConfigException;
use Closure;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class Injector
{
    protected ContainerInterface $container;

    /**
     * Injector constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Build instance of concrete class with resolve arguments.
     *
     * @param string|array $concrete Classname or definition array.
     * @param array $arguments Arguments which provides to class constructor when no __constructor() .
     * @return object Instance of abstract classname.
     *
     * @throws InvalidConfigException which can not resolve arguments or invalid array definitions provided
     */
    public function build($concrete, array $arguments = []): object
    {
        // Getting classname and constructor arguments
        if (is_string($concrete)) {
            $class = $concrete;
        } elseif (is_array($concrete)) {
            $class = $concrete['__class'] ?? null;
            if (is_null($class)) {
                throw new InvalidConfigException("No __class provided.");
            }
            unset($concrete['__class']);

            // Constructor arguments
            if (isset($concrete['__constructor()'])) {
                $arguments = $concrete['__constructor()'];
                unset($concrete['__constructor()']);
            }
        } else {
            throw new InvalidConfigException("Invalid classname provided.");
        }

        // Make reflection object
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new InvalidConfigException("Target class [$class] does not exist.", 0, $e);
        }

        // Check class for instantiable
        if (!$reflector->isInstantiable()) {
            throw new InvalidConfigException("Target [$class] is not instantiable.");
        }

        // Make instance
        $constructor = $reflector->getConstructor();
        $instance = is_null($constructor)
            ? new $class()
            : new $class(...$this->resolveDependencies($constructor->getParameters(), $arguments));

        // Return instance, if concrete is not array definitions
        if (is_string($concrete)) {
            return $instance;
        }

        // Call methods and inject parameters
        foreach ($concrete as $key => $value) {
            // Call method
            if (substr($key, -2) === '()') {
                $methodName = substr($key, 0, -2);
                try {
                    $method = $reflector->getMethod($methodName);
                } catch (ReflectionException $e) {
                    throw new InvalidConfigException(
                        "Target method [$methodName] does not exist in class [$class].",
                        0,
                        $e
                    );
                }
                $instance->$methodName(...$this->resolveDependencies($method->getParameters(), $value));
            } else {
                try {
                    // 
                    if (is_object($value) && $value instanceof Definition) {
                        $value = $this->invoke($value->getClosure());
                    }
                    $instance->$key = $value;
                } catch (Throwable $e) {
                    throw new InvalidConfigException("Failed to set [$key] property in class [$class].", 0, $e);
                }
            }
        }

        return $instance;
    }

    /**
     * Resolve parameters dependencies.
     *
     * @param ReflectionParameter[] $dependencies
     * @param array $parameters
     * @return array
     *
     * @throws InvalidConfigException
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $name = $dependency->getName();
            $className = $this->getParameterClassName($dependency);

            try {
                if (array_key_exists($name, $parameters)) {
                    $result = $parameters[$name];
                } elseif (is_null($className)) {
                    $result = $dependency->getDefaultValue();
                } else {
                    try {
                        $result = $this->container->get($className);
                    } catch (NotFoundExceptionInterface $e) {
                        $result = $dependency->getDefaultValue();
                    }
                }
            } catch (ReflectionException $e) {
                $in = $dependency->getDeclaringClass()
                    ? 'class ' . $dependency->getDeclaringClass()->getName()
                    : 'closure';
                throw new InvalidConfigException("Unresolvable dependency resolving [$dependency] in $in", 0, $e);
            }

            // Invoke ArgumentDefinition Closure
            if (is_object($result) && $result instanceof Definition) {
                $result = $this->invoke($result->getClosure());
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * @param ReflectionParameter $parameter
     * @return string|null
     */
    protected function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    /**
     * Call object method with resolve arguments.
     *
     * @param object $object
     * @param string $method
     * @param array $arguments Arguments which provides when call method.
     * @return mixed Method return value.
     *
     * @throws InvalidConfigException which can not resolve arguments.
     */
    public function invoke(object $object, string $method = '__invoke', array $arguments = [])
    {
        try {
            if ($object instanceof Closure) {
                $reflection = new ReflectionFunction($object);
                $parameters = $reflection->getParameters();
            } else {
                $reflector = new ReflectionClass($object);
                try {
                    $reflectorMethod = $reflector->getMethod($method);
                } catch (ReflectionException $e) {
                    throw new InvalidConfigException(
                        "Target method [$method] of class [" . get_class($object) . "] does not exist.", 0, $e
                    );
                }
                $parameters = $reflectorMethod->getParameters();
            }
        } catch (ReflectionException $e) {
            throw new InvalidConfigException("Failed to make reflection of class [" . get_class($object) . "].", 0, $e);
        }

        return $object->$method(...$this->resolveDependencies($parameters, $arguments));
    }
}
