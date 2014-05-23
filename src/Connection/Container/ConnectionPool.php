<?php
namespace Icecave\Manifold\Connection\Container;

use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The interface implemented by connection pools.
 */
class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * Construct a new connection pool.
     *
     * @param string                     $name        The connection pool name.
     * @param array<ConnectionInterface> $connections The connections.
     *
     * @throws Exception\EmptyConnectionContainerException If no connections are supplied.
     */
    public function __construct($name, array $connections)
    {
        if (count($connections) < 1) {
            throw new Exception\EmptyConnectionContainerException;
        }

        $this->name = $name;
        $this->connections = $connections;
    }

    /**
     * Get the connection container name.
     *
     * @return string The connection container name.
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the connections.
     *
     * @return array<ConnectionInterface> The connections.
     */
    public function connections()
    {
        return $this->connections;
    }

    private $name;
    private $connections;
}
