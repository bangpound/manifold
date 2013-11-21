<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Container\ConnectionPoolInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface;
use Icecave\Manifold\Replication\ReplicationTreeInterface;

/**
 * Represents a complete set of configuration settings.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Construct a new configuration.
     *
     * @param Map<string,ConnectionInterface>       $connections                 The defined connections.
     * @param array<string,ConnectionPoolInterface> $connectionPools             The defined connection pools.
     * @param ConnectionContainerSelectorInterface  $connectionContainerSelector The connection container selector.
     * @param array<ReplicationTreeInterface>       $replicationTrees            The replication trees.
     */
    public function __construct(
        Map $connections,
        array $connectionPools,
        ConnectionContainerSelectorInterface $connectionContainerSelector,
        array $replicationTrees
    ) {
        $this->connections = $connections;
        $this->connectionPools = $connectionPools;
        $this->connectionContainerSelector = $connectionContainerSelector;
        $this->replicationTrees = $replicationTrees;
    }

    /**
     * Get the defined connections.
     *
     * @return Map<string,ConnectionInterface> The defined connections.
     */
    public function connections()
    {
        return $this->connections;
    }

    /**
     * Get the defined connection pools.
     *
     * @return array<string,ConnectionPoolInterface> The defined connection pools.
     */
    public function connectionPools()
    {
        return $this->connectionPools;
    }

    /**
     * Get the connection container selector.
     *
     * @return ConnectionContainerSelectorInterface The connection container selector.
     */
    public function connectionContainerSelector()
    {
        return $this->connectionContainerSelector;
    }

    /**
     * Get the replication trees.
     *
     * @return array<ReplicationTreeInterface> The replication trees.
     */
    public function replicationTrees()
    {
        return $this->replicationTrees;
    }

    private $connections;
    private $connectionPools;
    private $connectionContainerSelector;
    private $replicationTrees;
}
