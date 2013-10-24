<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolSelectorInterface;
use Icecave\Manifold\Replication\ReplicationTreeInterface;
use PDO;

/**
 * Represents a complete set of configuration settings.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Construct a new configuration.
     *
     * @param Map<string,PDO>                     $connections            The defined connections.
     * @param Map<string,ConnectionPoolInterface> $connectionPools        The defined connection pools.
     * @param ConnectionPoolSelectorInterface     $connectionPoolSelector The connection pool selector.
     * @param Vector<ReplicationTreeInterface>    $replicationTrees       The replication trees.
     */
    public function __construct(
        Map $connections,
        Map $connectionPools,
        ConnectionPoolSelectorInterface $connectionPoolSelector,
        Vector $replicationTrees
    ) {
        $this->connections = $connections;
        $this->connectionPools = $connectionPools;
        $this->connectionPoolSelector = $connectionPoolSelector;
        $this->replicationTrees = $replicationTrees;
    }

    /**
     * Get the defined connections.
     *
     * @return Map<string,PDO> The defined connections.
     */
    public function connections()
    {
        return $this->connections;
    }

    /**
     * Get the defined connection pools.
     *
     * @return Map<string,ConnectionPoolInterface> The defined connection pools.
     */
    public function connectionPools()
    {
        return $this->connectionPools;
    }

    /**
     * Get the connection pool selector.
     *
     * @return ConnectionPoolSelectorInterface The connection pool selector.
     */
    public function connectionPoolSelector()
    {
        return $this->connectionPoolSelector;
    }

    /**
     * Get the replication trees.
     *
     * @return Vector<ReplicationTreeInterface> The replication trees.
     */
    public function replicationTrees()
    {
        return $this->replicationTrees;
    }

    private $connections;
    private $connectionPools;
    private $connectionPoolSelector;
    private $replicationTrees;
}
