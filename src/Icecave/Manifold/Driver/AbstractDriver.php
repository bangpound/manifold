<?php
namespace Icecave\Manifold\Driver;

use Icecave\Collections\Vector;
use Icecave\Manifold\Configuration\ConfigurationInterface;
use Icecave\Manifold\Configuration\Exception\UndefinedConnectionException;
use Icecave\Manifold\Connection\Facade\ConnectionFacadeInterface;

/**
 * An abstract base class for implementing drivers.
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * Create all PDO connection facades defined in the supplied configuration.
     *
     * @param ConfigurationInterface    $configuration The configuration to use.
     * @param array<integer,mixed>|null $attributes    The connection attributes to use.
     *
     * @return Vector<ConnectionFacadeInterface> The newly created connection facades.
     */
    public function createConnections(
        ConfigurationInterface $configuration,
        array $attributes = null
    ) {
        $connections = new Vector;
        foreach ($configuration->replicationTrees() as $replicationTree) {
            $connections->pushBack(
                $this->createConnection(
                    $configuration,
                    $replicationTree,
                    $attributes
                )
            );
        }

        return $connections;
    }

    /**
     * Create a PDO connection facade using the supplied configuration and
     * replication tree root connection name.
     *
     * @param ConfigurationInterface    $configuration  The configuration to use.
     * @param string                    $connectionName The name of the replication tree root connection.
     * @param array<integer,mixed>|null $attributes     The connection attributes to use.
     *
     * @return ConnectionFacadeInterface    The newly created connection facade.
     * @throws UndefinedConnectionException If no replication tree root connection is defined for the supplied name.
     */
    public function createConnectionByName(
        ConfigurationInterface $configuration,
        $connectionName,
        array $attributes = null
    ) {
        foreach ($configuration->replicationTrees() as $replicationTree) {
            if (
                $replicationTree->replicationRoot()->name() === $connectionName
            ) {
                return $this->createConnection(
                    $configuration,
                    $replicationTree,
                    $attributes
                );
            }
        }

        throw new UndefinedConnectionException($connectionName);
    }
}
