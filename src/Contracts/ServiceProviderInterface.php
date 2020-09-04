<?php

declare(strict_types=1);

namespace Azexsoft\Di\Contracts;

use Psr\Container\ContainerInterface;

interface ServiceProviderInterface
{
    /**
     * Registers classes in the container.
     *
     * @param ContainerInterface $container the container in which to register the services.
     */
    public function register(ContainerInterface $container): void;
}
