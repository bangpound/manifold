<?php
namespace Icecave\Manifold\Connection;

use PHPUnit_Framework_TestCase;

class ConnectionFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->driverOptions = array('foo' => 'bar');
        $this->factory = new ConnectionFactory($this->driverOptions);
    }

    public function testConstructor()
    {
        $this->assertSame($this->driverOptions, $this->factory->driverOptions());
    }

    public function testConstructorDefaults()
    {
        $this->factory = new ConnectionFactory;

        $this->assertNull($this->factory->driverOptions());
    }

    public function testCreate()
    {
        $expected = new LazyPdoConnection('dsn', 'username', 'password', array('foo' => 'bar'));

        $this->assertEquals($expected, $this->factory->create('dsn', 'username', 'password'));
    }
}
