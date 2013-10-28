<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Manifold\Connection\ConnectionPoolInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolSelectorInterface;
use Icecave\Manifold\Replication\ReplicationTreeInterface;
use PDO;

/**
 * The interface implemented by configuration instances.
 */
interface ConfigurationInterface
{
    /**
     * Get the defined connections.
     *
     * @return Map<string,PDO> The defined connections.
     */
    public function connections();

    /**
     * Get the defined connection pools.
     *
     * @return Map<string,ConnectionPoolInterface> The defined connection pools.
     */
    public function connectionPools();

    /**
     * Get the connection pool selector.
     *
     * @return ConnectionPoolSelectorInterface The connection pool selector.
     */
    public function connectionPoolSelector();

    /**
     * Get the replication trees.
     *
     * @return Vector<ReplicationTreeInterface> The replication trees.
     */
    public function replicationTrees();
}
