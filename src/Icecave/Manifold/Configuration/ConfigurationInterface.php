<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\ConnectionPoolInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface;
use Icecave\Manifold\Replication\ReplicationTreeInterface;

/**
 * The interface implemented by configuration instances.
 */
interface ConfigurationInterface
{
    /**
     * Get the defined connections.
     *
     * @return Map<string,ConnectionInterface> The defined connections.
     */
    public function connections();

    /**
     * Get the defined connection pools.
     *
     * @return Map<string,ConnectionPoolInterface> The defined connection pools.
     */
    public function connectionPools();

    /**
     * Get the connection container selector.
     *
     * @return ConnectionContainerSelectorInterface The connection container selector.
     */
    public function connectionContainerSelector();

    /**
     * Get the replication trees.
     *
     * @return Vector<ReplicationTreeInterface> The replication trees.
     */
    public function replicationTrees();
}
