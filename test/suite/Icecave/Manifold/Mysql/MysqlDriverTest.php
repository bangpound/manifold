<?php
namespace Icecave\Manifold\Mysql;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Manifold\Configuration\Configuration;
use Icecave\Manifold\Connection\Facade\PdoConnectionFacade;
use Icecave\Manifold\Replication\ConnectionSelector;
use Icecave\Manifold\Replication\QueryConnectionSelector;
use PDO;
use PHPUnit_Framework_TestCase;
use Phake;

class MysqlDriverTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->driver = new MysqlDriver;

        $this->connectionPoolSelector = Phake::mock('Icecave\Manifold\Connection\Pool\ConnectionPoolSelectorInterface');
        $this->replicationTreeA = Phake::mock('Icecave\Manifold\Replication\ReplicationTreeInterface');
        $this->replicationTreeA->id = 'A';
        $this->replicationTreeB = Phake::mock('Icecave\Manifold\Replication\ReplicationTreeInterface');
        $this->replicationTreeB->id = 'B';
        $this->configuration = new Configuration(
            new Map,
            new Map,
            $this->connectionPoolSelector,
            new Vector(array($this->replicationTreeA, $this->replicationTreeB))
        );

        $this->connectionSelector = Phake::mock('Icecave\Manifold\Replication\ConnectionSelectorInterface');

        $this->attributes = new Map(array(111 => 'foo'));
        $this->defaultAttributes = new Map(
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_AUTOCOMMIT => false,
            )
        );
        $this->expectedAttributes = $this->attributes->merge($this->defaultAttributes);
    }

    public function testCreateConnections()
    {
        $expected = new Vector(
            array(
                new PdoConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionPoolSelector,
                            new MysqlReplicationManager($this->replicationTreeA)
                        ),
                        new MysqlQueryDiscriminator
                    ),
                    $this->expectedAttributes
                ),
                new PdoConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionPoolSelector,
                            new MysqlReplicationManager($this->replicationTreeB)
                        ),
                        new MysqlQueryDiscriminator
                    ),
                    $this->expectedAttributes
                ),
            )
        );
        $actual = $this->driver->createConnections($this->configuration, $this->attributes);

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->connectionPoolSelector, $actual[0]->connectionSelector()->poolSelector());
        $this->assertSame($this->replicationTreeA, $actual[0]->connectionSelector()->replicationManager()->tree());
        $this->assertSame($this->connectionPoolSelector, $actual[1]->connectionSelector()->poolSelector());
        $this->assertSame($this->replicationTreeB, $actual[1]->connectionSelector()->replicationManager()->tree());
    }

    public function testCreateConnectionsDefaults()
    {
        $expected = new Vector(
            array(
                new PdoConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionPoolSelector,
                            new MysqlReplicationManager($this->replicationTreeA)
                        ),
                        new MysqlQueryDiscriminator
                    ),
                    $this->defaultAttributes
                ),
                new PdoConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionPoolSelector,
                            new MysqlReplicationManager($this->replicationTreeB)
                        ),
                        new MysqlQueryDiscriminator
                    ),
                    $this->defaultAttributes
                ),
            )
        );
        $actual = $this->driver->createConnections($this->configuration);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateConnection()
    {
        $expected = new PdoConnectionFacade(
            new QueryConnectionSelector(
                new ConnectionSelector(
                    $this->connectionPoolSelector,
                    new MysqlReplicationManager($this->replicationTreeA)
                ),
                new MysqlQueryDiscriminator
            ),
            $this->expectedAttributes
        );
        $actual = $this->driver->createConnection($this->configuration, $this->replicationTreeA, $this->attributes);

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->connectionPoolSelector, $actual->connectionSelector()->poolSelector());
        $this->assertSame($this->replicationTreeA, $actual->connectionSelector()->replicationManager()->tree());
    }

    public function testCreateConnectionDefaults()
    {
        $expected = new PdoConnectionFacade(
            new QueryConnectionSelector(
                new ConnectionSelector(
                    $this->connectionPoolSelector,
                    new MysqlReplicationManager($this->replicationTreeA)
                ),
                new MysqlQueryDiscriminator
            ),
            $this->defaultAttributes
        );
        $actual = $this->driver->createConnection($this->configuration, $this->replicationTreeA);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateConnectionFromSelector()
    {
        $expected = new PdoConnectionFacade(
            new QueryConnectionSelector(
                $this->connectionSelector,
                new MysqlQueryDiscriminator
            ),
            $this->expectedAttributes
        );
        $actual = $this->driver->createConnectionFromSelector($this->connectionSelector, $this->attributes);

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->connectionSelector, $actual->connectionSelector());
    }

    public function testCreateConnectionFromSelectorDefaults()
    {
        $expected = new PdoConnectionFacade(
            new QueryConnectionSelector(
                $this->connectionSelector,
                new MysqlQueryDiscriminator
            ),
            $this->defaultAttributes
        );
        $actual = $this->driver->createConnectionFromSelector($this->connectionSelector);

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->connectionSelector, $actual->connectionSelector());
    }
}
