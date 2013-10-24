<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use PDO;

/**
 * The connection is not currently replicating.
 */
class NotReplicatingException extends Exception
{
    /**
     * Construct a new not replicating exception.
     *
     * @param PDO            $connection The connection.
     * @param Exception|null $previous   The cause, if available.
     */
    public function __construct(PDO $connection, Exception $previous = null)
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
     * @return PDO The connection.
     */
    public function connection()
    {
        return $this->connection;
    }

    private $connection;
}
