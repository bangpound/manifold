<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
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
        $this->pool = new ConnectionPool(
            'pool',
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
        $this->assertSame($this->connectionA, $this->strategy->select($this->manager, $this->pool));
        Phake::verifyNoInteraction($this->manager);
    }

    public function testSelectLogging()
    {
        $this->assertSame($this->connectionA, $this->strategy->select($this->manager, $this->pool, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting any connection from pool {pool}.',
                array('pool' => 'pool')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from pool {pool}. Any connection is acceptable.',
                array('connection' => 'A', 'pool' => 'pool')
            )
        );
        Phake::verifyNoInteraction($this->manager);
    }
}
