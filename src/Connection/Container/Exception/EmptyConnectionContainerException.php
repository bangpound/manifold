<?php
namespace Icecave\Manifold\Connection\Container\Exception;

use Exception;

/**
 * Connection containers cannot be empty.
 */
final class EmptyConnectionContainerException extends Exception
{
    /**
     * Construct a new empty connection container exception.
     *
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct(Exception $previous = null)
    {
        parent::__construct(
            'Connection containers cannot be empty.',
            0,
            $previous
        );
    }
}
