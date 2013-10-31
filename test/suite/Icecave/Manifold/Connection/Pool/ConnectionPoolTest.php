<?php
namespace Icecave\Manifold\Connection\Pool;

use Icecave\Collections\Vector;
use Phake;
use PHPUnit_Framework_TestCase;

class ConnectionPoolTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connections = new Vector(
            array(
                Phake::mock('PDO'),
                Phake::mock('PDO'),
            )
        );
        $this->pool = new ConnectionPool($this->connections);
    }

    public function testConstructor()
    {
        $this->assertSame($this->connections, $this->pool->connections());
    }

    public function testConstructorFailureEmpty()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\EmptyConnectionPoolException');

        new ConnectionPool(new Vector);
    }
}
