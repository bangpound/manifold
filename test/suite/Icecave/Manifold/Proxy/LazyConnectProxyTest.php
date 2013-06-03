<?php
namespace Icecave\Manifold\Proxy;

use Eloquent\Liberator\Liberator;
use PDO;
use Phake;
use PHPUnit_Framework_TestCase;

class LazyConnectProxyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phake::mock('PDO');
        $this->reflector = Phake::mock('ReflectionClass');
        $this->proxy = Phake::partialMock(
            __NAMESPACE__ . '\LazyConnectProxy',
            'dsn',
            'username',
            'password',
            array(10101 => 'foo'),
            $this->reflector
        );

        Phake::when($this->reflector)
            ->newInstance(Phake::anyParameters())
            ->thenReturn($this->connection);
    }

    public function testConstructorDefaults()
    {
        $proxy = new LazyConnectProxy('dsn');

        $reflector = Liberator::liberate($proxy)->reflector;

        $this->assertInstanceOf('ReflectionClass', $reflector);
        $this->assertSame('PDO', $reflector->getName());
    }

    public function testInnerConnection()
    {
        $connection1 = $this->proxy->innerConnection();
        $connection2 = $this->proxy->innerConnection();

        Phake::verify($this->proxy, Phake::times(2))->connect();

        $this->assertSame($this->connection, $connection1);
        $this->assertSame($this->connection, $connection2);
    }

    public function testIsConnected()
    {
        $this->assertFalse($this->proxy->isConnected());

        $this->proxy->connect();

        $this->assertTrue($this->proxy->isConnected());
    }

    public function testConnect()
    {
        $this->proxy->connect();

        // Second invocation should be a no-op ...
        $this->proxy->connect();

        Phake::inOrder(
            Phake::verify($this->proxy)->beforeConnect(),
            Phake::verify($this->reflector)->newInstance('dsn', 'username', 'password', array(10101 => 'foo')),
            Phake::verify($this->proxy)->afterConnect()
        );
    }

    public function testGetAttribute()
    {
        $this->assertSame('foo', $this->proxy->getAttribute(10101));
        $this->assertNull($this->proxy->getAttribute(20202));

        Phake::verifyNoInteraction($this->connection);
    }

    public function testGetAttributeWhenConnected()
    {
        $this->proxy->connect();

        $this->assertNull($this->proxy->getAttribute(10101));

        Phake::verify($this->connection)->getAttribute(10101);
    }

    public function testSetAttribute()
    {
        $this->proxy->setAttribute(10101, 'bar');

        $this->assertSame('bar', $this->proxy->getAttribute(10101));

        Phake::verifyNoInteraction($this->connection);
    }

    public function testSetAttributeBeforeConnected()
    {
        $this->proxy->setAttribute(10101, 'bar');

        $this->proxy->connect();

        Phake::verify($this->reflector)->newInstance('dsn', 'username', 'password', array(10101 => 'bar'));
    }

    public function testSetAttributeWhenConnected()
    {
        $this->proxy->connect();

        $this->assertNull($this->proxy->setAttribute(10101, 'bar'));

        Phake::verify($this->connection)->setAttribute(10101, 'bar');
    }
}
