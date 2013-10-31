<?php
namespace Icecave\Manifold\Connection;

use PDO;
use Phake;
use PHPUnit_Framework_TestCase;

class LazyPdoConnectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phake::partialMock(
            __NAMESPACE__ . '\LazyPdoConnection',
            'dsn',
            'username',
            'password',
            array(10101 => 'foo')
        );

        Phake::when($this->connection)
            ->constructParent(Phake::anyParameters())
            ->thenReturn(null);
    }

    public function testConstructor()
    {
        $this->assertSame('dsn', $this->connection->dsn());
        $this->assertSame('username', $this->connection->username());
        $this->assertSame('password', $this->connection->password());
        $this->assertSame(array(10101 => 'foo'), $this->connection->attributes());
    }

    public function testConstructorDefaults()
    {
        $this->connection = new LazyPdoConnection('dsn');

        $this->assertNull($this->connection->username());
        $this->assertNull($this->connection->password());
        $this->assertSame(array(), $this->connection->attributes());
    }

    public function testIsConnected()
    {
        $this->assertFalse($this->connection->isConnected());

        $this->connection->connect();

        $this->assertTrue($this->connection->isConnected());
    }

    public function testConnect()
    {
        $this->connection->connect();

        // Second invocation should be a no-op ...
        $this->connection->connect();

        Phake::inOrder(
            Phake::verify($this->connection)->beforeConnect(),
            Phake::verify($this->connection)->constructParent('dsn', 'username', 'password', array(10101 => 'foo')),
            Phake::verify($this->connection)->afterConnect()
        );
    }

    public function testGetAttribute()
    {
        $this->assertSame('foo', $this->connection->getAttribute(10101));
        $this->assertNull($this->connection->getAttribute(20202));

        Phake::verify($this->connection, Phake::never())->constructParent(Phake::anyParameters());
    }

    public function testGetAttributeWhenConnected()
    {
        $this->connection->connect();

        // We induce a failure to verify that the parent getAttribute method is called.
        // This fails because constructParent() is mocked.
        $this->setExpectedException(
            'PHPUnit_Framework_Error_Warning',
            'SQLSTATE[00000]: No error: PDO constructor was not called'
        );
        $this->assertNull($this->connection->getAttribute(10101));
    }

    public function testSetAttribute()
    {
        $this->connection->setAttribute(10101, 'bar');

        $this->assertSame('bar', $this->connection->getAttribute(10101));

        Phake::verify($this->connection, Phake::never())->constructParent(Phake::anyParameters());
    }

    public function testSetAttributeBeforeConnected()
    {
        $this->connection->setAttribute(10101, 'bar');

        $this->connection->connect();

        Phake::verify($this->connection)->constructParent('dsn', 'username', 'password', array(10101 => 'bar'));
    }

    public function testSetAttributeWhenConnected()
    {
        $this->connection->connect();

        // We induce a failure to verify that the parent getAttribute method is called.
        // This fails because constructParent() is mocked.
        $this->setExpectedException(
            'PHPUnit_Framework_Error_Warning',
            'SQLSTATE[00000]: No error: PDO constructor was not called'
        );
        $this->connection->setAttribute(10101, 'bar');
    }
}
