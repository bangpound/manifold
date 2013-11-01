<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;

/**
 * Selects the connection with the least replication delay.
 */
class AnyStrategy implements SelectionStrategyInterface
{
    /**
     * Get a single connection from a pool.
     *
     * @param ReplicationManagerInterface $replicationManager The replication manager to use.
     * @param ConnectionPoolInterface     $pool               The pool to select from.
     *
     * @return ConnectionInterface            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        ReplicationManagerInterface $replicationManager,
        ConnectionPoolInterface $pool
    ) {
        foreach ($pool->connections() as $connection) {
            break;
        }

        return $connection;
    }
}
