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
                Phake::mock('Icecave\Manifold\Connection\ConnectionInterface'),
                Phake::mock('Icecave\Manifold\Connection\ConnectionInterface'),
            )
        );
        $this->pool = new ConnectionPool('name', $this->connections);
    }

    public function testConstructor()
    {
        $this->assertSame('name', $this->pool->name());
        $this->assertSame($this->connections, $this->pool->connections());
    }

    public function testConstructorFailureEmpty()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\EmptyConnectionPoolException');

        new ConnectionPool('name', new Vector);
    }
}
