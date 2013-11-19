<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Container\ConnectionPool;
use PHPUnit_Framework_TestCase;
use Phake;

class AnyStrategyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->strategy = new AnyStrategy;

        $this->manager = Phake::mock('Icecave\Manifold\Replication\ReplicationManagerInterface');
        $this->connectionA = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionA)->name()->thenReturn('A');
        $this->connectionB = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionB)->name()->thenReturn('B');
        $this->container = new ConnectionPool(
            'container',
            new Vector(
                array(
                    $this->connectionA,
                    $this->connectionB,
                )
            )
        );

        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
    }

    public function testSelect()
    {
        $this->assertSame($this->connectionA, $this->strategy->select($this->manager, $this->container));
        Phake::verifyNoInteraction($this->manager);
    }

    public function testSelectLogging()
    {
        $this->assertSame($this->connectionA, $this->strategy->select($this->manager, $this->container, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting any connection from container {container}.',
                array('container' => 'container')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from container {container}. Any connection is acceptable.',
                array('connection' => 'A', 'container' => 'container')
            )
        );
        Phake::verifyNoInteraction($this->manager);
    }

    public function testString()
    {
        $expected = "Any connection.";

        $this->assertSame($expected, $this->strategy->string());
        $this->assertSame($expected, strval($this->strategy));
    }
}
