<?php
namespace Icecave\Manifold\Driver;

use Icecave\Manifold\Configuration\ConfigurationInterface;
use Icecave\Manifold\Configuration\Exception\UndefinedConnectionException;
use Icecave\Manifold\Connection\Facade\ConnectionFacadeInterface;
use Icecave\Manifold\Replication\ConnectionSelectorInterface;
use Icecave\Manifold\Replication\ReplicationTreeInterface;

/**
 * The interface implemented by drivers.
 */
interface DriverInterface
{
    /**
     * Create all PDO connection facades defined in the supplied configuration.
     *
     * @param ConfigurationInterface    $configuration The configuration to use.
     * @param array<integer,mixed>|null $attributes    The connection attributes to use.
     *
     * @return array<ConnectionFacadeInterface> The newly created connection facades.
     */
    public function createConnections(
        ConfigurationInterface $configuration,
        array $attributes = null
    );

    /**
     * Create the first PDO connection facade defined in the supplied
     * configuration.
     *
     * @param ConfigurationInterface    $configuration The configuration to use.
     * @param array<integer,mixed>|null $attributes    The connection attributes to use.
     *
     * @return ConnectionFacadeInterface The newly created connection facade.
     */
    public function createFirstConnection(
        ConfigurationInterface $configuration,
        array $attributes = null
    );

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
    );

    /**
     * Create a PDO connection facade using the supplied configuration and
     * replication tree.
     *
     * @param ConfigurationInterface    $configuration   The configuration to use.
     * @param ReplicationTreeInterface  $replicationTree The replication tree to use.
     * @param array<integer,mixed>|null $attributes      The connection attributes to use.
     *
     * @return ConnectionFacadeInterface The newly created connection facade.
     */
    public function createConnection(
        ConfigurationInterface $configuration,
        ReplicationTreeInterface $replicationTree,
        array $attributes = null
    );

    /**
     * Create a PDO connection facade using the supplied connection selector.
     *
     * @param ConnectionSelectorInterface $connectionSelector The connection selector to use.
     * @param array<integer,mixed>|null   $attributes         The connection attributes to use.
     *
     * @return ConnectionFacadeInterface The newly created connection facade.
     */
    public function createConnectionFromSelector(
        ConnectionSelectorInterface $connectionSelector,
        array $attributes = null
    );
}
