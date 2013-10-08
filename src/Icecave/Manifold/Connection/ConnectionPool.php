<?php
namespace Icecave\Manifold\Connection;

use Icecave\Collections\Vector;

/**
 * The interface implemented by connection pools.
 */
class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * Construct a new connection pool.
     *
     * @param Vector<PDO> $connections The connections.
     */
    public function __construct(Vector $connections)
    {
        if ($connections->count() < 1) {
            throw new Exception\EmptyConnectionPoolException;
        }

        $this->connections = $connections;
    }

    /**
     * Get the connections.
     *
     * @return Vector<PDO> The connections.
     */
    public function connections()
    {
        return $this->connections;
    }

    private $connections;
}
