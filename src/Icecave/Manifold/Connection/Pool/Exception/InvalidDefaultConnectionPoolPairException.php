<?php
namespace Icecave\Manifold\Connection\Pool\Exception;

use Exception;
use Icecave\Manifold\Connection\Pool\ConnectionPoolPairInterface;

/**
 * The supplied default read/write pair is invalid.
 */
final class InvalidDefaultConnectionPoolPairException extends Exception
{
    /**
     * Construct a new invalid default read/write pair exception.
     *
     * @param ConnectionPoolPairInterface $pair     The supplied connection pool pair.
     * @param Exception|null              $previous The cause, if available.
     */
    public function __construct(
        ConnectionPoolPairInterface $pair,
        Exception $previous = null
    ) {
        $this->pair = $pair;

        parent::__construct(
            'Invalid default read/write connection pool pair supplied.',
            0,
            $previous
        );
    }

    /**
     * Get the supplied connection pool pair.
     *
     * @return ConnectionPoolPairInterface The connection pool pair.
     */
    public function pair()
    {
        return $this->pair;
    }

    private $pair;
}
