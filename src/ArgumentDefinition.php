<?php

declare(strict_types=1);

namespace Azexsoft\Di;

use Closure;

/**
 * Class ArgumentDefinition required for resolve method argument in dependency injection container.
 */
final class ArgumentDefinition
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
