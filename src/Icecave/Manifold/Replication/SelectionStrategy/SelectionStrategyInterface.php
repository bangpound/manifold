<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use PDO;

/**
 * The interface implemented by connection pool member selection strategies.
 */
interface SelectionStrategyInterface
{
    /**
     * Get a single connection from a pool.
     *
     * @param ReplicationManagerInterface $manager The replication manager to use.
     * @param ConnectionPoolInterface     $pool    The pool to select from.
     *
     * @return PDO                            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        ReplicationManagerInterface $manager,
        ConnectionPoolInterface $pool
    );
}
