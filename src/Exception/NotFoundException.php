<?php

declare(strict_types=1);

namespace Azexsoft\Di\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException is thrown when no entry was found in the container.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
