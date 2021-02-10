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

class Injector
{
    private ContainerInterface $container;

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
     * @param string $concrete Concrete classname.
     * @param array $arguments Arguments which provides to class constructor.
     * @return object Instance of abstract classname.
     *
     * @throws InvalidConfigException which can not resolve arguments
     */
    public function build(string $concrete, array $arguments = []): object
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new InvalidConfigException("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new InvalidConfigException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete();
        }

        return new $concrete(...$this->resolveDependencies($constructor->getParameters(), $arguments));
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
    private function resolveDependencies(array $dependencies, array $parameters = []): array
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
                $in = $dependency->getDeclaringClass() ? 'class ' . $dependency->getDeclaringClass()->getName(
                    ) : 'closure';
                throw new InvalidConfigException("Unresolvable dependency resolving [$dependency] in $in", 0, $e);
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
    private function getParameterClassName(ReflectionParameter $parameter): ?string
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
    public function invoke(object $object, string $method = '__invoke', array $arguments = []): mixed
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
