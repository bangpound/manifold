<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\ConnectionPoolInterface;
use Icecave\Manifold\Connection\ConnectionSelectorInterface;
use Icecave\Manifold\Replication\ReplicationTreeInterface;
use PDO;

/**
 * The interface implemented by Manifold configuration instances.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Construct a new Manifold configuration.
     *
     * @param Map<string,PDO>                     $connections        The defined connections.
     * @param Map<string,ConnectionPoolInterface> $connectionPools    The defined connection pools.
     * @param ConnectionSelectorInterface         $connectionSelector The connection selector.
     * @param Vector<ReplicationTreeInterface>    $replicationTrees   The replication trees.
     */
    public function __construct(
        Map $connections,
        Map $connectionPools,
        ConnectionSelectorInterface $connectionSelector,
        Vector $replicationTrees
    ) {
        $this->connections = $connections;
        $this->connectionPools = $connectionPools;
        $this->connectionSelector = $connectionSelector;
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
     * Get the connection selector.
     *
     * @return ConnectionSelectorInterface The connection selector.
     */
    public function connectionSelector()
    {
        return $this->connectionSelector;
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
    private $connectionSelector;
    private $replicationTrees;
}
