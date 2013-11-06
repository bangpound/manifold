<?php
namespace Icecave\Manifold\Connection;

use PDO;
use PHPUnit_Framework_TestCase;

class ConnectionFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->attributes = array('foo' => 'bar');
        $this->factory = new ConnectionFactory($this->attributes);
    }

    public function testConstructor()
    {
        $this->assertSame($this->attributes, $this->factory->attributes());
    }

    public function testConstructorDefaults()
    {
        $this->factory = new ConnectionFactory;

        $this->assertSame(array(PDO::ATTR_PERSISTENT => false), $this->factory->attributes());
    }

    public function testCreate()
    {
        $expected = new LazyConnection('name', 'dsn', 'username', 'password', array('foo' => 'bar'));

        $this->assertEquals($expected, $this->factory->create('name', 'dsn', 'username', 'password'));
    }
}
