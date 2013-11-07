<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Psr\Log\LoggerInterface;

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
     * @param LoggerInterface|null        $logger             The logger to use.
     *
     * @return ConnectionInterface            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        ReplicationManagerInterface $replicationManager,
        ConnectionPoolInterface $pool,
        LoggerInterface $logger = null
    ) {
        if (null !== $logger) {
            $logger->debug(
                'Selecting any connection from pool {pool}.',
                array('pool' => $pool->name())
            );
        }

        foreach ($pool->connections() as $connection) {
            break;
        }

        if (null !== $logger) {
            $logger->debug(
                'Connection {connection} selected from pool {pool}. ' .
                    'Any connection is acceptable.',
                array(
                    'connection' => $connection->name(),
                    'pool' => $pool->name(),
                )
            );
        }

        return $connection;
    }
}
