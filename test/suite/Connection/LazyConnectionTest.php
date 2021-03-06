<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\Authentication\Credentials;
use Icecave\Manifold\Authentication\CredentialsProvider;
use PDO;
use PHPUnit_Framework_TestCase;
use Phake;

class LazyConnectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->credentialsProvider = new CredentialsProvider(
            new Credentials('username', 'password'),
            array(
                'foo' => new Credentials('fooUsername', 'fooPassword'),
            )
        );
        $this->pdoConnectionFactory = Phake::mock('Icecave\Manifold\Connection\PdoConnectionFactoryInterface');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->connection = new LazyConnection(
            'name',
            'driver:host=host',
            $this->credentialsProvider,
            array(PDO::ATTR_TIMEOUT => 'foo'),
            $this->pdoConnectionFactory,
            $this->logger
        );

        $this->pdoConnection = Phake::mock('PDO');
        Phake::when($this->pdoConnection)->setAttribute(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->pdoConnectionFactory)->createConnection(Phake::anyParameters())
            ->thenReturn($this->pdoConnection);

        $this->statement = Phake::mock('PDOStatement');
    }

    public function testConstructor()
    {
        $this->assertSame('name', $this->connection->name());
        $this->assertSame('driver:host=host', $this->connection->dsn());
        $this->assertSame($this->credentialsProvider, $this->connection->credentialsProvider());
        $this->assertSame(array(PDO::ATTR_TIMEOUT => 'foo'), $this->connection->attributes());
        $this->assertSame($this->pdoConnectionFactory, $this->connection->pdoConnectionFactory());
        $this->assertSame($this->logger, $this->connection->logger());
    }

    public function testClone()
    {
        $this->connection->connection();
        $clone = $this->connection->cloneConnection();
        $clone->setAttribute(PDO::ATTR_CURSOR_NAME, 'bar');

        $this->assertFalse($clone->isConnected());
        $this->assertSame('name', $clone->name());
        $this->assertSame('driver:host=host', $clone->dsn());
        $this->assertSame($this->credentialsProvider, $clone->credentialsProvider());
        $this->assertSame(array(PDO::ATTR_TIMEOUT => 'foo', PDO::ATTR_CURSOR_NAME => 'bar'), $clone->attributes());
        $this->assertSame($this->pdoConnectionFactory, $clone->pdoConnectionFactory());
        $this->assertSame($this->logger, $clone->logger());

        $this->assertSame(array(PDO::ATTR_TIMEOUT => 'foo'), $this->connection->attributes());
    }

    public function testConstructorDefaults()
    {
        $this->connection = new LazyConnection('name', 'driver:host=host');

        $this->assertEquals(new CredentialsProvider(), $this->connection->credentialsProvider());
        $this->assertSame(array(), $this->connection->attributes());
        $this->assertEquals(new PdoConnectionFactory(), $this->connection->pdoConnectionFactory());
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
        Phake::verify($this->pdoConnectionFactory)
            ->createConnection('driver:host=host', 'username', 'password', array(PDO::ATTR_TIMEOUT => 'foo'));
    }

    public function testConnect()
    {
        $this->connection = Phake::partialMock(
            __NAMESPACE__ . '\LazyConnection',
            'name',
            'driver:host=host',
            $this->credentialsProvider,
            array(PDO::ATTR_TIMEOUT => 'foo'),
            $this->pdoConnectionFactory,
            $this->logger
        );
        $this->connection->connect();

        $this->assertSame($this->pdoConnection, $this->connection->connection());

        $this->connection->connect();

        $this->assertSame($this->pdoConnection, $this->connection->connection());
        Phake::inOrder(
            Phake::verify($this->connection)->beforeConnect(),
            Phake::verify($this->logger)->debug(
                'Establishing connection {connection} to {dsn}.',
                array('connection' => 'name', 'dsn' => 'driver:host=host')
            ),
            Phake::verify($this->pdoConnectionFactory)
                ->createConnection('driver:host=host', 'username', 'password', array(PDO::ATTR_TIMEOUT => 'foo')),
            Phake::verify($this->connection)->afterConnect()
        );
    }

    public function testConnectCredentialsOverride()
    {
        $this->connection = new LazyConnection(
            'foo',
            'driver:host=host',
            $this->credentialsProvider,
            null,
            $this->pdoConnectionFactory
        );
        $this->connection->connect();

        $this->assertSame($this->pdoConnection, $this->connection->connection());

        $this->connection->connect();

        $this->assertSame($this->pdoConnection, $this->connection->connection());
        Phake::verify($this->pdoConnectionFactory)
            ->createConnection('driver:host=host', 'fooUsername', 'fooPassword', array());
    }

    public function testConnectDefaults()
    {
        $this->connection = Phake::partialMock(
            __NAMESPACE__ . '\LazyConnection',
            'name',
            'driver:host=host',
            null,
            null,
            $this->pdoConnectionFactory
        );
        $this->connection->connect();

        $this->assertSame($this->pdoConnection, $this->connection->connection());

        $this->connection->connect();

        $this->assertSame($this->pdoConnection, $this->connection->connection());
        Phake::inOrder(
            Phake::verify($this->connection)->beforeConnect(),
            Phake::verify($this->pdoConnectionFactory)->createConnection('driver:host=host', null, null, array()),
            Phake::verify($this->connection)->afterConnect()
        );
    }

    public function testConnection()
    {
        $this->assertSame($this->pdoConnection, $this->connection->connection());
        $this->assertSame($this->pdoConnection, $this->connection->connection());
        Phake::verify($this->pdoConnectionFactory)
            ->createConnection('driver:host=host', 'username', 'password', array(PDO::ATTR_TIMEOUT => 'foo'));
    }

    public function testPrepare()
    {
        $statement = 'SELECT * FROM foo.bar';
        $attributes = array(456 => 'bar');
        Phake::when($this->pdoConnection)->prepare($statement, $attributes)->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->connection->prepare($statement, $attributes));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Preparing statement {statement} on {connection}.',
                array('statement' => $statement, 'connection' => 'name')
            ),
            Phake::verify($this->pdoConnection)->prepare($statement, $attributes)
        );
    }

    public function testQuery()
    {
        $statement = 'SELECT * FROM foo.bar';
        Phake::when($this->pdoConnection)->query($statement, 'one', 'two')->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->connection->query($statement, 'one', 'two'));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} on {connection}.',
                array('statement' => $statement, 'connection' => 'name')
            ),
            Phake::verify($this->pdoConnection)->query($statement, 'one', 'two')
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
        Phake::when($this->pdoConnection)->exec($statement)->thenReturn(111);

        $this->assertSame(111, $this->connection->exec($statement));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} on {connection}.',
                array('statement' => $statement, 'connection' => 'name')
            ),
            Phake::verify($this->pdoConnection)->exec($statement)
        );
    }

    public function testInTransaction()
    {
        Phake::when($this->pdoConnection)->inTransaction()->thenReturn(true)->thenReturn(false);

        $this->assertFalse($this->connection->inTransaction());

        $this->connection->connect();

        $this->assertTrue($this->connection->inTransaction());
        $this->assertFalse($this->connection->inTransaction());
    }

    public function testBeginTransaction()
    {
        Phake::when($this->pdoConnection)->beginTransaction()->thenReturn(true);

        $this->assertTrue($this->connection->beginTransaction());
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Beginning transaction on {connection}.',
                array('connection' => 'name')
            ),
            Phake::verify($this->pdoConnection)->beginTransaction()
        );
    }

    public function testCommit()
    {
        Phake::when($this->pdoConnection)->commit()->thenReturn(true);

        $this->assertTrue($this->connection->commit());
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Committing transaction on {connection}.',
                array('connection' => 'name')
            ),
            Phake::verify($this->pdoConnection)->commit()
        );
    }

    public function testRollBack()
    {
        Phake::when($this->pdoConnection)->rollBack()->thenReturn(true);

        $this->assertTrue($this->connection->rollBack());
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Rolling back transaction on {connection}.',
                array('connection' => 'name')
            ),
            Phake::verify($this->pdoConnection)->rollBack()
        );
    }

    public function testLastInsertId()
    {
        Phake::when($this->pdoConnection)->lastInsertId('foo')->thenReturn('111');

        $this->assertSame('0', $this->connection->lastInsertId('foo'));

        $this->connection->connect();

        $this->assertSame('111', $this->connection->lastInsertId('foo'));
    }

    public function testErrorCode()
    {
        Phake::when($this->pdoConnection)->errorCode()->thenReturn('11111');

        $this->assertNull($this->connection->errorCode());

        $this->connection->connect();

        $this->assertSame('11111', $this->connection->errorCode());
    }

    public function testErrorInfo()
    {
        Phake::when($this->pdoConnection)->errorInfo()->thenReturn(array('11111', 222, 'foo'));

        $this->assertSame(array('', null, null), $this->connection->errorInfo());

        $this->connection->connect();

        $this->assertSame(array('11111', 222, 'foo'), $this->connection->errorInfo());
    }

    public function testQuote()
    {
        Phake::when($this->pdoConnection)->quote('foo', 111)->thenReturn('bar');
        Phake::when($this->pdoConnection)->quote('foo', PDO::PARAM_STR)->thenReturn('baz');

        $this->assertSame('bar', $this->connection->quote('foo', 111));
        $this->assertSame('baz', $this->connection->quote('foo'));
    }

    public function testSetAttribute()
    {
        $this->assertTrue($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));
        $this->assertSame('bar', $this->connection->getAttribute(PDO::ATTR_TIMEOUT));
        Phake::verify($this->logger)->debug(
            'Setting attribute {attribute} to {value} on {connection}.',
            array(
                'attribute' => 'PDO::ATTR_TIMEOUT',
                'value' => 'bar',
                'connection' => 'name',
            )
        );
        Phake::verify($this->pdoConnectionFactory, Phake::never())->createConnection(Phake::anyParameters());
    }

    public function testSetAttributeBeforeConnected()
    {
        $this->assertTrue($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));

        $this->connection->connect();

        Phake::verify($this->pdoConnectionFactory)
            ->createConnection('driver:host=host', 'username', 'password', array(PDO::ATTR_TIMEOUT => 'bar'));
    }

    public function testSetAttributeWhenConnected()
    {
        $this->connection->connect();

        $this->assertTrue($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));
        Phake::verify($this->pdoConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'bar');
    }

    public function testSetAttributeWhenConnectedFailure()
    {
        Phake::when($this->pdoConnection)->setAttribute(Phake::anyParameters())->thenReturn(false);
        $this->connection->connect();

        $this->assertFalse($this->connection->setAttribute(PDO::ATTR_TIMEOUT, 'bar'));
        Phake::verify($this->pdoConnection)->setAttribute(PDO::ATTR_TIMEOUT, 'bar');
    }

    public function testGetAttribute()
    {
        $this->assertSame('foo', $this->connection->getAttribute(PDO::ATTR_TIMEOUT));
        $this->assertNull($this->connection->getAttribute(20202));
        Phake::verify($this->pdoConnectionFactory, Phake::never())->createConnection(Phake::anyParameters());
    }

    public function testGetAttributeWhenConnected()
    {
        Phake::when($this->pdoConnection)->getAttribute(PDO::ATTR_TIMEOUT)->thenReturn('bar');
        $this->connection->connect();

        $this->assertSame('bar', $this->connection->getAttribute(PDO::ATTR_TIMEOUT));
    }

    public function testGetAttributeDriverName()
    {
        $this->assertSame('driver', $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function testGetAttributeDriverNameDefault()
    {
        $this->connection = new LazyConnection('name', 'dsn');

        $this->assertSame('mysql', $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function testConnections()
    {
        $this->assertSame(array($this->connection), $this->connection->connections());
    }
}
