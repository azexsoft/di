<?php

declare(strict_types=1);

namespace Azexsoft\Di\Contracts;

use Azexsoft\Di\Container;

interface ServiceProviderInterface
{
    /**
     * Registers classes in the container.
     *
     * @param Container $container the container in which to register the services.
     */
    public function register(Container $container): void;
}
