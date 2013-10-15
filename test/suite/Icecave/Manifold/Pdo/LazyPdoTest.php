<?php
namespace Icecave\Manifold\Pdo;

use PDO;
use Phake;
use PHPUnit_Framework_TestCase;

class LazyPdoTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->proxy = Phake::partialMock(
            __NAMESPACE__ . '\LazyPdo',
            'dsn',
            'username',
            'password',
            array(10101 => 'foo')
        );

        Phake::when($this->proxy)
            ->constructParent(Phake::anyParameters())
            ->thenReturn(null);
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
            Phake::verify($this->proxy)->constructParent('dsn', 'username', 'password', array(10101 => 'foo')),
            Phake::verify($this->proxy)->afterConnect()
        );
    }

    public function testGetAttribute()
    {
        $this->assertSame('foo', $this->proxy->getAttribute(10101));
        $this->assertNull($this->proxy->getAttribute(20202));

        Phake::verify($this->proxy, Phake::never())->constructParent(Phake::anyParameters());
    }

    public function testGetAttributeWhenConnected()
    {
        $this->proxy->connect();

        // We induce a failure to verify that the parent getAttribute method is called.
        // This fails because constructParent() is mocked.
        $this->setExpectedException('ErrorException', 'SQLSTATE[00000]: No error: PDO constructor was not called');
        $this->assertNull($this->proxy->getAttribute(10101));
    }

    public function testSetAttribute()
    {
        $this->proxy->setAttribute(10101, 'bar');

        $this->assertSame('bar', $this->proxy->getAttribute(10101));

        Phake::verify($this->proxy, Phake::never())->constructParent(Phake::anyParameters());
    }

    public function testSetAttributeBeforeConnected()
    {
        $this->proxy->setAttribute(10101, 'bar');

        $this->proxy->connect();

        Phake::verify($this->proxy)->constructParent('dsn', 'username', 'password', array(10101 => 'bar'));
    }

    public function testSetAttributeWhenConnected()
    {
        $this->proxy->connect();

        // We induce a failure to verify that the parent getAttribute method is called.
        // This fails because constructParent() is mocked.
        $this->setExpectedException('ErrorException', 'SQLSTATE[00000]: No error: PDO constructor was not called');
        $this->proxy->setAttribute(10101, 'bar');
    }
}
