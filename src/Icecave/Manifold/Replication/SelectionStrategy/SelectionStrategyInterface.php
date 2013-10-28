<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use PDO;

/**
 * The interface implemented by connection pool member selection strategies.
 */
interface SelectionStrategyInterface
{
    /**
     * Get a single connection from a pool.
     *
     * @param ConnectionPoolInterface $pool The pool to select from.
     *
     * @return PDO                            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(ConnectionPoolInterface $pool);
}
