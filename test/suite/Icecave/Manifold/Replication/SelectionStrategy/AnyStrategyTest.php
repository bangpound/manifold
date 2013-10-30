<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\LazyPdoConnection;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use PHPUnit_Framework_TestCase;
use Phake;

class AnyStrategyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->strategy = new AnyStrategy;

        $this->manager = Phake::mock('Icecave\Manifold\Replication\ReplicationManagerInterface');
        $this->connectionA = new LazyPdoConnection('a');
        $this->connectionB = new LazyPdoConnection('b');
        $this->pool = new ConnectionPool(
            new Vector(
                array(
                    $this->connectionA,
                    $this->connectionB,
                )
            )
        );
    }

    public function testSelect()
    {
        $this->assertSame($this->connectionA, $this->strategy->select($this->manager, $this->pool));
        Phake::verifyNoInteraction($this->manager);
    }
}
