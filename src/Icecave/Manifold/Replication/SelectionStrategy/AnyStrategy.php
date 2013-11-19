<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Selects the connection with the least replication delay.
 */
class AnyStrategy implements SelectionStrategyInterface
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
    ) {
        if (null !== $logger) {
            $logger->debug(
                'Selecting any connection from container {container}.',
                array('container' => $container->name())
            );
        }

        foreach ($container->connections() as $connection) {
            break;
        }

        if (null !== $logger) {
            $logger->debug(
                'Connection {connection} selected from container ' .
                    '{container}. Any connection is acceptable.',
                array(
                    'connection' => $connection->name(),
                    'container' => $container->name(),
                )
            );
        }

        return $connection;
    }

    /**
     * Generate a string representation of this strategy.
     *
     * @return string The generated string representation of this strategy.
     */
    public function string()
    {
        return 'Any connection.';
    }

    /**
     * Generate a string representation of this strategy.
     *
     * @return string The generated string representation of this strategy.
     */
    public function __toString()
    {
        return $this->string();
    }
}
