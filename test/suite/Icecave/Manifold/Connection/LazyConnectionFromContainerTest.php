<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\Replication\SelectionStrategy\AnyStrategy;
use PDO;
use PHPUnit_Framework_TestCase;
use Phake;

class LazyConnectionFromContainerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = Phake::mock(__NAMESPACE__ . '\Container\ConnectionContainerInterface');
        $this->replicationManager = Phake::mock('Icecave\Manifold\Replication\ReplicationManagerInterface');
        $this->strategy = Phake::mock('Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface');
        $this->attributes = array(PDO::ATTR_TIMEOUT => 'foo');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->connection = new LazyConnectionFromContainer(
            $this->container,
            $this->replicationManager,
            $this->strategy,
            $this->attributes,
            $this->logger
        );

        $this->innerConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->statement = Phake::mock('PDOStatement');

        Phake::when($this->strategy)->select($this->replicationManager, $this->container, $this->logger)
            ->thenReturn($this->innerConnection);
        Phake::when($this->innerConnection)->setAttribute(Phake::anyParameters())->thenReturn(true);
    }

    public function testConstructor()
    {
        $this->assertSame($this->container, $this->connection->container());
        $this->assertSame($this->replicationManager, $this->connection->replicationManager());
        $this->assertSame($this->strategy, $this->connection->strategy());
        $this->assertSame($this->attributes, $this->connection->attributes());
        $this->assertSame($this->logger, $this->connection->logger());
    }

    public function testConstructorDefaults()
    {
        $this->connection = new LazyConnectionFromContainer(
            $this->container,
            $this->replicationManager
        );

        $this->assertEquals(new AnyStrategy, $this->connection->strategy());
        $this->assertSame(array(), $this->connection->attributes());
        $this->assertNull($this->connection->logger());
    }

    public function testSetLogger()
    {
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->connection->setLogger($this->logger);

        $this->assertSame($this->logger, $this->connection->logger());
    }

    public function testIsConnected()
    {
        $this->assertFalse($this->connection->isConnected());
        $this->assertFalse($this->connection->isConnected());

        $this->connection->connect();

        $this->assertTrue($this->connection->isConnected());
        $this->assertTrue($this->connection->isConnected());
    }

    public function testConnect()
    {
        $this->connection->connect();

        $this->assertSame($this->innerConnection, $this->connection->connection());

        $this->connection->connect();

        $this->assertSame($this->innerConnection, $this->connection->connection());
        Phake::inOrder(
            Phake::verify($this->strategy)->select($this->replicationManager, $this->container, $this->logger),
            Phake::verify($this->innerConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'foo')
        );
    }

    public function testConnection()
    {
        $this->assertSame($this->innerConnection, $this->connection->connection());
        $this->assertSame($this->innerConnection, $this->connection->connection());
        Phake::inOrder(
            Phake::verify($this->strategy)->select($this->replicationManager, $this->container, $this->logger),
            Phake::verify($this->innerConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'foo')
        );
    }

    public function testDsn()
    {
        Phake::when($this->innerConnection)->dsn()->thenReturn('dsn');

        $this->assertSame('dsn', $this->connection->dsn());
        Phake::inOrder(
            Phake::verify($this->strategy)->select($this->replicationManager, $this->container, $this->logger),
            Phake::verify($this->innerConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'foo')
        );
    }

    public function testPrepare()
    {
        $statement = 'SELECT * FROM foo.bar';
        $attributes = array(456 => 'bar');
        Phake::when($this->innerConnection)->prepare($statement, $attributes)->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->connection->prepare($statement, $attributes));
        Phake::verify($this->innerConnection)->prepare($statement, $attributes);
    }

    public function testQuery()
    {
        $statement = 'SELECT * FROM foo.bar';
        Phake::when($this->innerConnection)->query($statement, 'one', 'two')->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->connection->query($statement, 'one', 'two'));
        Phake::verify($this->innerConnection)->query($statement, 'one', 'two');
    }

    public function testExec()
    {
        $statement = 'SELECT * FROM foo.bar';
        Phake::when($this->innerConnection)->exec($statement)->thenReturn(111);

        $this->assertSame(111, $this->connection->exec($statement));
        Phake::verify($this->innerConnection)->exec($statement);
    }

    public function testInTransaction()
    {
        Phake::when($this->innerConnection)->inTransaction()->thenReturn(true)->thenReturn(false);

        $this->assertFalse($this->connection->inTransaction());

        $this->connection->connect();

        $this->assertTrue($this->connection->inTransaction());
        $this->assertFalse($this->connection->inTransaction());
    }

    public function testBeginTransaction()
    {
        Phake::when($this->innerConnection)->beginTransaction()->thenReturn(true);

        $this->assertTrue($this->connection->beginTransaction());
        Phake::verify($this->innerConnection)->beginTransaction();
    }

    public function testCommit()
    {
        Phake::when($this->innerConnection)->commit()->thenReturn(true);

        $this->assertTrue($this->connection->commit());
        Phake::verify($this->innerConnection)->commit();
    }

    public function testRollBack()
    {
        Phake::when($this->innerConnection)->rollBack()->thenReturn(true);

        $this->assertTrue($this->connection->rollBack());
        Phake::verify($this->innerConnection)->rollBack();
    }

    public function testLastInsertId()
    {
        Phake::when($this->innerConnection)->lastInsertId('foo')->thenReturn('111');

        $this->assertSame('0', $this->connection->lastInsertId('foo'));

        $this->connection->connect();

        $this->assertSame('111', $this->connection->lastInsertId('foo'));
    }

    public function testErrorCode()
    {
        Phake::when($this->innerConnection)->errorCode()->thenReturn('11111');

        $this->assertNull($this->connection->errorCode());

        $this->connection->connect();

        $this->assertSame('11111', $this->connection->errorCode());
    }

    public function testErrorInfo()
    {
        Phake::when($this->innerConnection)->errorInfo()->thenReturn(array('11111', 222, 'foo'));

        $this->assertSame(array('', null, null), $this->connection->errorInfo());

        $this->connection->connect();

        $this->assertSame(array('11111', 222, 'foo'), $this->connection->errorInfo());
    }

    public function testQuote()
    {
        Phake::when($this->innerConnection)->quote('foo', 111)->thenReturn('bar');
        Phake::when($this->innerConnection)->quote('foo', PDO::PARAM_STR)->thenReturn('baz');

        $this->assertSame('bar', $this->connection->quote('foo', 111));
        $this->assertSame('baz', $this->connection->quote('foo'));
    }

    public function testSetAttribute()
    {
        $this->assertTrue($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));
        $this->assertSame('bar', $this->connection->getAttribute(PDO::ATTR_TIMEOUT));
        Phake::verify($this->strategy, Phake::never())->select(Phake::anyParameters());
    }

    public function testSetAttributeBeforeConnected()
    {
        $this->assertTrue($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));

        $this->connection->connect();

        Phake::inOrder(
            Phake::verify($this->strategy)->select($this->replicationManager, $this->container, $this->logger),
            Phake::verify($this->innerConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'bar')
        );
    }

    public function testSetAttributeWhenConnected()
    {
        $this->connection->connect();

        $this->assertTrue($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));
        Phake::verify($this->innerConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'bar');
    }

    public function testSetAttributeWhenConnectedFailure()
    {
        Phake::when($this->innerConnection)->setAttribute(Phake::anyParameters())->thenReturn(false);
        $this->connection->connect();

        $this->assertFalse($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));
        Phake::verify($this->innerConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'bar');
    }

    public function testGetAttribute()
    {
        $this->assertSame('foo', $this->connection->getAttribute(PDO::ATTR_TIMEOUT));
        $this->assertNull($this->connection->getAttribute(20202));
        Phake::verify($this->strategy, Phake::never())->select(Phake::anyParameters());
    }

    public function testGetAttributeWhenConnected()
    {
        Phake::when($this->innerConnection)->getAttribute(PDO::ATTR_TIMEOUT)->thenReturn('bar');
        $this->connection->connect();

        $this->assertSame('bar', $this->connection->getAttribute(PDO::ATTR_TIMEOUT));
    }

    public function testConnections()
    {
        $this->assertSame(array($this->connection), $this->connection->connections());
    }
}
