<?php
namespace Icecave\Manifold\Configuration;

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
     * @return array<string,ConnectionInterface> The defined connections.
     */
    public function connections();

    /**
     * Get the defined connection pools.
     *
     * @return array<string,ConnectionPoolInterface> The defined connection pools.
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
     * @return array<ReplicationTreeInterface> The replication trees.
     */
    public function replicationTrees();
}
