<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The connection is not currently replicating.
 */
final class NotReplicatingException extends Exception
{
    /**
     * Construct a new not replicating exception.
     *
     * @param ConnectionInterface $connection The connection.
     * @param Exception|null      $previous   The cause, if available.
     */
    public function __construct(ConnectionInterface $connection, Exception $previous = null)
    {
        $this->connection = $connection;

        parent::__construct(
            'The connection is not currently replicating.',
            0,
            $previous
        );
    }

    /**
     * Get the connection.
     *
     * @return ConnectionInterface The connection.
     */
    public function connection()
    {
        return $this->connection;
    }

    private $connection;
}
