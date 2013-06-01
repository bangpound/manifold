<?php
namespace Icecave\Manifold\Proxy;

use Eloquent\Liberator\Liberator;
use PDO;
use Phake;
use PHPUnit_Framework_TestCase;

class LazyConnectorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phake::mock('PDO');
        $this->arguments = array(1, 2, 3);
        $this->reflector = Phake::mock('ReflectionClass');
        $this->proxy = new LazyConnector($this->arguments, $this->reflector);

        Phake::when($this->reflector)
            ->newInstanceArgs(Phake::anyParameters())
            ->thenReturn($this->connection);
    }

    public function testConstructorDefaults()
    {
        $proxy = new LazyConnector($this->arguments);

        $reflector = Liberator::liberate($proxy)->reflector;

        $this->assertInstanceOf('ReflectionClass', $reflector);
        $this->assertSame('PDO', $reflector->getName());
    }

    public function testInnerConnection()
    {
        $connection1 = $this->proxy->innerConnection();
        $connection2 = $this->proxy->innerConnection();

        Phake::verify($this->reflector)->newInstanceArgs($this->arguments);

        $this->assertSame($this->connection, $connection1);
        $this->assertSame($this->connection, $connection2);
    }

    public function testIsConnected()
    {
        $this->assertFalse($this->proxy->isConnected());

        $this->proxy->innerConnection();

        $this->assertTrue($this->proxy->isConnected());
    }
}
