<?php

declare(strict_types=1);

namespace Azexsoft\Di;

use Closure;

/**
 * Definition required for resolve argument or property in dependency injection container.
 */
final class Definition
{
    private Closure $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public function getClosure(): Closure
    {
        return $this->closure;
    }
}
