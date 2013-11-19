<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * The interface implemented by connection container member selection
 * strategies.
 */
interface SelectionStrategyInterface
{
    /**
     * Get a single connection from a container.
     *
     * @param ReplicationManagerInterface  $replicationManager The replication manager to use.
     * @param ConnectionContainerInterface $container          The container to select from.
     * @param LoggerInterface|null         $logger             The logger to use.
     *
     * @return ConnectionInterface            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        ReplicationManagerInterface $replicationManager,
        ConnectionContainerInterface $container,
        LoggerInterface $logger = null
    );

    /**
     * Generate a string representation of this strategy.
     *
     * @return string The generated string representation of this strategy.
     */
    public function string();

    /**
     * Generate a string representation of this strategy.
     *
     * @return string The generated string representation of this strategy.
     */
    public function __toString();
}
