<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The supplied connection does not exist in this replication tree.
 */
class UnknownConnectionException extends Exception
{
    /**
     * Construct a new unknown connection exception.
     *
     * @param ConnectionInterface $connection The unknown connection.
     * @param Exception|null      $previous   The cause, if available.
     */
    public function __construct(
        ConnectionInterface $connection,
        Exception $previous = null
    ) {
        $this->connection = $connection;

        parent::__construct('Unknown connection.', 0, $previous);
    }

    /**
     * Get the unknown connection.
     *
     * @return ConnectionInterface The connection.
     */
    public function connection()
    {
        return $this->connection;
    }

    private $connection;
}
