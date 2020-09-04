<?php

declare(strict_types=1);

namespace Azexsoft\Di\Contracts;

interface DeferredServiceProviderInterface extends ServiceProviderInterface
{
    /**
     * @return string[] a list of IDs of services provided
     */
    public function provides(): array;
}
