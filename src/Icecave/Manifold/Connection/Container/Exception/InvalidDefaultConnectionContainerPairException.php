<?php
namespace Icecave\Manifold\Connection\Container\Exception;

use Exception;
use Icecave\Manifold\Connection\Container\ConnectionContainerPairInterface;

/**
 * The supplied default read/write pair is invalid.
 */
final class InvalidDefaultConnectionContainerPairException extends Exception
{
    /**
     * Construct a new invalid default read/write pair exception.
     *
     * @param ConnectionContainerPairInterface $pair     The supplied connection container pair.
     * @param Exception|null                   $previous The cause, if available.
     */
    public function __construct(
        ConnectionContainerPairInterface $pair,
        Exception $previous = null
    ) {
        $this->pair = $pair;

        parent::__construct(
            'Invalid default read/write connection container pair supplied.',
            0,
            $previous
        );
    }

    /**
     * Get the supplied connection container pair.
     *
     * @return ConnectionContainerPairInterface The connection container pair.
     */
    public function pair()
    {
        return $this->pair;
    }

    private $pair;
}
