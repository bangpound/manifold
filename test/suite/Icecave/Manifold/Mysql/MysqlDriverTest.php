<?php
namespace Icecave\Manifold\Mysql;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Manifold\Configuration\Configuration;
use Icecave\Manifold\Connection\Facade\ConnectionFacade;
use Icecave\Manifold\Replication\ConnectionSelector;
use Icecave\Manifold\Replication\QueryConnectionSelector;
use PDO;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Icecave\Manifold\Mysql\MysqlDriver
 * @covers \Icecave\Manifold\Driver\AbstractDriver
 */
class MysqlDriverTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->driver = new MysqlDriver;

        $this->connectionContainerSelector = Phake::mock(
            'Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface'
        );
        $this->replicationTreeA = Phake::mock('Icecave\Manifold\Replication\ReplicationTreeInterface');
        $this->replicationTreeB = Phake::mock('Icecave\Manifold\Replication\ReplicationTreeInterface');
        $this->configuration = new Configuration(
            new Map,
            new Map,
            $this->connectionContainerSelector,
            array($this->replicationTreeA, $this->replicationTreeB)
        );

        $this->connectionSelector = Phake::mock('Icecave\Manifold\Replication\ConnectionSelectorInterface');

        $this->attributes = array(111 => 'foo');
        $this->defaultAttributes = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_AUTOCOMMIT => false,
        );
        $this->expectedAttributes = array(
            111 => 'foo',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_AUTOCOMMIT => false,
        );

        $this->connectionA = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionA)->name()->thenReturn('A');
        $this->connectionB = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionB)->name()->thenReturn('B');

        Phake::when($this->replicationTreeA)->replicationRoot()->thenReturn($this->connectionA);
        Phake::when($this->replicationTreeB)->replicationRoot()->thenReturn($this->connectionB);
    }

    public function testCreateConnections()
    {
        $expected = new Vector(
            array(
                new ConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionContainerSelector,
                            new MysqlReplicationManager($this->replicationTreeA)
                        ),
                        new MysqlQueryDiscriminator
                    ),
                    $this->expectedAttributes
                ),
                new ConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionContainerSelector,
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
        $this->assertSame($this->connectionContainerSelector, $actual[0]->connectionSelector()->containerSelector());
        $this->assertSame($this->replicationTreeA, $actual[0]->connectionSelector()->replicationManager()->tree());
        $this->assertSame($this->connectionContainerSelector, $actual[1]->connectionSelector()->containerSelector());
        $this->assertSame($this->replicationTreeB, $actual[1]->connectionSelector()->replicationManager()->tree());
    }

    public function testCreateConnectionsDefaults()
    {
        $expected = new Vector(
            array(
                new ConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionContainerSelector,
                            new MysqlReplicationManager($this->replicationTreeA)
                        ),
                        new MysqlQueryDiscriminator
                    ),
                    $this->defaultAttributes
                ),
                new ConnectionFacade(
                    new QueryConnectionSelector(
                        new ConnectionSelector(
                            $this->connectionContainerSelector,
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

    public function testCreateConnectionByName()
    {
        $expected = new ConnectionFacade(
            new QueryConnectionSelector(
                new ConnectionSelector(
                    $this->connectionContainerSelector,
                    new MysqlReplicationManager($this->replicationTreeA)
                ),
                new MysqlQueryDiscriminator
            ),
            $this->expectedAttributes
        );
        $actual = $this->driver->createConnectionByName($this->configuration, 'A', $this->attributes);

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->connectionContainerSelector, $actual->connectionSelector()->containerSelector());
        $this->assertSame($this->replicationTreeA, $actual->connectionSelector()->replicationManager()->tree());
    }

    public function testCreateConnectionByNameFailureUndefined()
    {
        $this->setExpectedException('Icecave\Manifold\Configuration\Exception\UndefinedConnectionException');
        $this->driver->createConnectionByName($this->configuration, 'C');
    }

    public function testCreateConnection()
    {
        $expected = new ConnectionFacade(
            new QueryConnectionSelector(
                new ConnectionSelector(
                    $this->connectionContainerSelector,
                    new MysqlReplicationManager($this->replicationTreeA)
                ),
                new MysqlQueryDiscriminator
            ),
            $this->expectedAttributes
        );
        $actual = $this->driver->createConnection($this->configuration, $this->replicationTreeA, $this->attributes);

        $this->assertEquals($expected, $actual);
        $this->assertSame($this->connectionContainerSelector, $actual->connectionSelector()->containerSelector());
        $this->assertSame($this->replicationTreeA, $actual->connectionSelector()->replicationManager()->tree());
    }

    public function testCreateConnectionDefaults()
    {
        $expected = new ConnectionFacade(
            new QueryConnectionSelector(
                new ConnectionSelector(
                    $this->connectionContainerSelector,
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
        $expected = new ConnectionFacade(
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
        $expected = new ConnectionFacade(
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
