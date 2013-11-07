<?php
namespace Icecave\Manifold\Connection;

use PDO;
use Phake;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;

class ConnectionFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->attributes = array('foo' => 'bar');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->factory = new ConnectionFactory($this->attributes, $this->logger);
    }

    public function testConstructor()
    {
        $this->assertSame($this->attributes, $this->factory->attributes());
        $this->assertSame($this->logger, $this->factory->logger());
    }

    public function testConstructorDefaults()
    {
        $this->factory = new ConnectionFactory;

        $this->assertSame(array(PDO::ATTR_PERSISTENT => false), $this->factory->attributes());
        $this->assertEquals(new NullLogger, $this->factory->logger());
    }

    public function testSetLogger()
    {
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->factory->setLogger($this->logger);

        $this->assertSame($this->logger, $this->factory->logger());
    }

    public function testCreate()
    {
        $expected = new LazyConnection('name', 'dsn', 'username', 'password', array('foo' => 'bar'), $this->logger);

        $this->assertEquals($expected, $this->factory->create('name', 'dsn', 'username', 'password'));
    }
}
