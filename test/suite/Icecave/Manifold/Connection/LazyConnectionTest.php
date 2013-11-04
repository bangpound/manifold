<?php
namespace Icecave\Manifold\Connection;

use PDO;
use PHPUnit_Framework_TestCase;
use Phake;
use Psr\Log\NullLogger;

class LazyConnectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->connection = Phake::partialMock(
            __NAMESPACE__ . '\LazyConnection',
            'name',
            'dsn',
            'username',
            'password',
            array(10101 => 'foo'),
            $this->logger
        );

        $this->innerConnection = Phake::mock('PDO');
        Phake::when($this->connection)->createConnection(Phake::anyParameters())->thenReturn($this->innerConnection);

        $this->statement = Phake::mock('PDOStatement');
    }

    public function testConstructor()
    {
        $this->assertSame('name', $this->connection->name());
        $this->assertSame('dsn', $this->connection->dsn());
        $this->assertSame('username', $this->connection->username());
        $this->assertSame('password', $this->connection->password());
        $this->assertSame(array(10101 => 'foo'), $this->connection->attributes());
        $this->assertSame($this->logger, $this->connection->logger());
    }

    public function testConstructorDefaults()
    {
        $this->connection = new LazyConnection('name', 'dsn');

        $this->assertNull($this->connection->username());
        $this->assertNull($this->connection->password());
        $this->assertSame(array(), $this->connection->attributes());
        $this->assertEquals(new NullLogger, $this->connection->logger());
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
        Phake::verify($this->connection)->createConnection('dsn', 'username', 'password', array(10101 => 'foo'));
    }

    public function testConnect()
    {
        $this->connection->connect();

        $this->assertSame($this->innerConnection, $this->connection->connection());

        $this->connection->connect();

        $this->assertSame($this->innerConnection, $this->connection->connection());
        Phake::inOrder(
            Phake::verify($this->connection)->beforeConnect(),
            Phake::verify($this->logger)->debug(
                'Establishing connection {connection} to {dsn}.',
                array('connection' => "'name'", 'dsn' => "'dsn'")
            ),
            Phake::verify($this->connection)->createConnection('dsn', 'username', 'password', array(10101 => 'foo')),
            Phake::verify($this->connection)->afterConnect()
        );
    }

    public function testConnectDefaults()
    {
        $this->connection = Phake::partialMock(__NAMESPACE__ . '\LazyConnection', 'name', 'dsn');
        Phake::when($this->connection)->createConnection(Phake::anyParameters())->thenReturn($this->innerConnection);
        $this->connection->connect();

        $this->assertSame($this->innerConnection, $this->connection->connection());

        $this->connection->connect();

        $this->assertSame($this->innerConnection, $this->connection->connection());
        Phake::inOrder(
            Phake::verify($this->connection)->beforeConnect(),
            Phake::verify($this->connection)->createConnection('dsn', null, null, array()),
            Phake::verify($this->connection)->afterConnect()
        );
    }

    public function testConnectFailureReal()
    {
        Phake::when($this->connection)->createConnection(Phake::anyParameters())->thenCallParent();

        $this->setExpectedException('PDOException', 'invalid data source name');
        $this->connection->connect();
    }

    public function testConnection()
    {
        $this->assertSame($this->innerConnection, $this->connection->connection());
        $this->assertSame($this->innerConnection, $this->connection->connection());
        Phake::verify($this->connection)->createConnection('dsn', 'username', 'password', array(10101 => 'foo'));
    }

    public function testPrepare()
    {
        $statement = 'SELECT * FROM foo.bar';
        $attributes = array(456 => 'bar');
        Phake::when($this->innerConnection)->prepare($statement, $attributes)->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->connection->prepare($statement, $attributes));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Preparing statement {statement} on {connection}.',
                array('statement' => var_export($statement, true), 'connection' => "'name'")
            ),
            Phake::verify($this->innerConnection)->prepare($statement, $attributes)
        );
    }

    public function testQuery()
    {
        $statement = 'SELECT * FROM foo.bar';
        Phake::when($this->innerConnection)->query($statement, 'one', 'two')->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->connection->query($statement, 'one', 'two'));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Querying statement {statement} on {connection}.',
                array('statement' => var_export($statement, true), 'connection' => "'name'")
            ),
            Phake::verify($this->innerConnection)->query($statement, 'one', 'two')
        );
    }

    public function testQueryFailureNoStatement()
    {
        $this->setExpectedException('InvalidArgumentException', 'PDO::query() expects at least 1 parameter, 0 given');
        $this->connection->query();
    }

    public function testExec()
    {
        $statement = 'SELECT * FROM foo.bar';
        Phake::when($this->innerConnection)->exec($statement)->thenReturn(111);

        $this->assertSame(111, $this->connection->exec($statement));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} on {connection}.',
                array('statement' => var_export($statement, true), 'connection' => "'name'")
            ),
            Phake::verify($this->innerConnection)->exec($statement)
        );
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
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Beginning transaction on {connection}.',
                array('connection' => "'name'")
            ),
            Phake::verify($this->innerConnection)->beginTransaction()
        );
    }

    public function testCommit()
    {
        Phake::when($this->innerConnection)->commit()->thenReturn(true);

        $this->assertTrue($this->connection->commit());
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Committing transaction on {connection}.',
                array('connection' => "'name'")
            ),
            Phake::verify($this->innerConnection)->commit()
        );
    }

    public function testRollBack()
    {
        Phake::when($this->innerConnection)->rollBack()->thenReturn(true);

        $this->assertTrue($this->connection->rollBack());
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Rolling back transaction on {connection}.',
                array('connection' => "'name'")
            ),
            Phake::verify($this->innerConnection)->rollBack()
        );
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
        $this->connection->setAttribute(10101, 'bar');

        $this->assertSame('bar', $this->connection->getAttribute(10101));
        Phake::verify($this->connection, Phake::never())->createConnection(Phake::anyParameters());
    }

    public function testSetAttributeBeforeConnected()
    {
        $this->connection->setAttribute(10101, 'bar');
        $this->connection->connect();

        Phake::verify($this->connection)->createConnection('dsn', 'username', 'password', array(10101 => 'bar'));
    }

    public function testSetAttributeWhenConnected()
    {
        $this->connection->connect();
        $this->connection->setAttribute(10101, 'bar');

        Phake::verify($this->innerConnection)->setAttribute(10101, 'bar');
    }

    public function testGetAttribute()
    {
        $this->assertSame('foo', $this->connection->getAttribute(10101));
        $this->assertNull($this->connection->getAttribute(20202));

        Phake::verify($this->connection, Phake::never())->createConnection(Phake::anyParameters());
    }

    public function testGetAttributeWhenConnected()
    {
        Phake::when($this->innerConnection)->getAttribute(10101)->thenReturn('bar');
        $this->connection->connect();

        $this->assertSame('bar', $this->connection->getAttribute(10101));
    }
}
