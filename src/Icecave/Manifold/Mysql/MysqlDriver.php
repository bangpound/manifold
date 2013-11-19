<?php
namespace Icecave\Manifold\Mysql;

use Icecave\Manifold\Configuration\ConfigurationInterface;
use Icecave\Manifold\Connection\Facade\ConnectionFacade;
use Icecave\Manifold\Connection\Facade\ConnectionFacadeInterface;
use Icecave\Manifold\Driver\AbstractDriver;
use Icecave\Manifold\Replication\ConnectionSelector;
use Icecave\Manifold\Replication\ConnectionSelectorInterface;
use Icecave\Manifold\Replication\QueryConnectionSelector;
use Icecave\Manifold\Replication\ReplicationTreeInterface;
use PDO;

/**
 * A driver for MySQL.
 */
class MysqlDriver extends AbstractDriver
{
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
    ) {
        return $this->createConnectionFromSelector(
            new ConnectionSelector(
                $configuration->connectionContainerSelector(),
                new MysqlReplicationManager($replicationTree)
            ),
            $attributes
        );
    }

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
    ) {
        if (null === $attributes) {
            $attributes = array();
        }

        $attributes[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $attributes[PDO::ATTR_AUTOCOMMIT] = false;

        return new ConnectionFacade(
            new QueryConnectionSelector(
                $connectionSelector,
                new MysqlQueryDiscriminator
            ),
            $attributes
        );
    }
}
