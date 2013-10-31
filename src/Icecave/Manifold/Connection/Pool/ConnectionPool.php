<?php
namespace Icecave\Manifold\Connection\Pool;

use Icecave\Collections\Vector;
use PDO;

/**
 * The interface implemented by connection pools.
 */
class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * Construct a new connection pool.
     *
     * @param string      $name        The connection pool name.
     * @param Vector<PDO> $connections The connections.
     *
     * @throws Exception\EmptyConnectionPoolException If no connections are supplied.
     */
    public function __construct($name, Vector $connections)
    {
        if ($connections->count() < 1) {
            throw new Exception\EmptyConnectionPoolException;
        }

        $this->name = $name;
        $this->connections = $connections;
    }

    /**
     * Get the connection pool name.
     *
     * @return string The connection pool name.
     */
    public function name()
    {
        return $this->name;
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

    private $name;
    private $connections;
}
