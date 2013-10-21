<?php
namespace Icecave\Manifold\Connection\Pool\Exception;

use Exception;

/**
 * Connection pools cannot be empty.
 */
final class EmptyConnectionPoolException extends Exception
{
    /**
     * Construct a new empty connection pool exception.
     *
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct(Exception $previous = null)
    {
        parent::__construct('Connection pools cannot be empty.', 0, $previous);
    }
}
