<?php
namespace Icecave\Manifold\Mysql;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Manifold\Configuration\ConfigurationInterface;
use Icecave\Manifold\Connection\Facade\PdoConnectionFacade;
use Icecave\Manifold\Connection\Facade\PdoConnectionFacadeInterface;
use Icecave\Manifold\Driver\DriverInterface;
use Icecave\Manifold\Replication\ConnectionSelector;
use Icecave\Manifold\Replication\ConnectionSelectorInterface;
use Icecave\Manifold\Replication\QueryConnectionSelector;
use Icecave\Manifold\Replication\ReplicationTreeInterface;
use PDO;

/**
 * A driver for MySQL.
 */
class MysqlDriver implements DriverInterface
{
    /**
     * Create all PDO connection facades defined in the supplied configuration.
     *
     * @param ConfigurationInterface  $configuration The configuration to use.
     * @param Map<integer,mixed>|null $attributes    The connection attributes to use.
     *
     * @return Vector<PdoConnectionFacadeInterface> The newly created connection facades.
     */
    public function createConnections(
        ConfigurationInterface $configuration,
        Map $attributes = null
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
     * replication tree.
     *
     * @param ConfigurationInterface   $configuration   The configuration to use.
     * @param ReplicationTreeInterface $replicationTree The replication tree to use.
     * @param Map<integer,mixed>|null  $attributes      The connection attributes to use.
     *
     * @return PdoConnectionFacadeInterface The newly created connection facade.
     */
    public function createConnection(
        ConfigurationInterface $configuration,
        ReplicationTreeInterface $replicationTree,
        Map $attributes = null
    ) {
        return $this->createConnectionFromSelector(
            new ConnectionSelector(
                $configuration->connectionPoolSelector(),
                new MysqlReplicationManager($replicationTree)
            ),
            $attributes
        );
    }

    /**
     * Create a PDO connection facade using the supplied connection selector.
     *
     * @param ConnectionSelectorInterface $connectionSelector The connection selector to use.
     * @param Map<integer,mixed>|null     $attributes         The connection attributes to use.
     *
     * @return PdoConnectionFacadeInterface The newly created connection facade.
     */
    public function createConnectionFromSelector(
        ConnectionSelectorInterface $connectionSelector,
        Map $attributes = null
    ) {
        if (null === $attributes) {
            $attributes = new Map;
        }

        $attributes->set(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $attributes->set(PDO::ATTR_PERSISTENT, false);
        $attributes->set(PDO::ATTR_AUTOCOMMIT, false);

        return new PdoConnectionFacade(
            new QueryConnectionSelector(
                $connectionSelector,
                new MysqlQueryDiscriminator
            ),
            $attributes
        );
    }
}
