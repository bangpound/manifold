<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\LazyPdoConnection;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Icecave\Manifold\Replication\SelectionStrategy\LeastDelayStrategy
 * @covers \Icecave\Manifold\Replication\SelectionStrategy\AbstractSelectionStrategy
 */
class LeastDelayStrategyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->manager = Phake::mock('Icecave\Manifold\Replication\ReplicationManagerInterface');
        $this->threshold = new Duration(444);
        $this->clock = Phake::partialMock('Icecave\Chrono\Clock\SystemClock');
        $this->strategy = new LeastDelayStrategy($this->manager, $this->threshold, $this->clock);

        $this->connectionA = new LazyPdoConnection('a');
        $this->connectionB = new LazyPdoConnection('b');
        $this->connectionC = new LazyPdoConnection('c');
        $this->pool = new ConnectionPool(
            new Vector(
                array(
                    $this->connectionA,
                    $this->connectionB,
                    $this->connectionC,
                )
            )
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->manager, $this->strategy->manager());
        $this->assertSame($this->threshold, $this->strategy->threshold());
        $this->assertSame($this->clock, $this->strategy->clock());
    }

    public function testConstructorDefaults()
    {
        $this->strategy = new LeastDelayStrategy($this->manager);

        $this->assertNull($this->strategy->threshold());
        $this->assertInstanceOf('Icecave\Chrono\Clock\SystemClock', $this->strategy->clock());
    }

    public function testConstructorNormalization()
    {
        $this->strategy = new LeastDelayStrategy($this->manager, 111);

        $this->assertSame(111, $this->strategy->threshold()->totalSeconds());
    }

    public function testSelect()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->pool));
    }

    public function testSelectNoThreshold()
    {
        $this->strategy = new LeastDelayStrategy($this->manager, null, $this->clock);
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->pool));
    }

    public function testSelectFailureThreshold()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(666));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(555));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(777));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->pool);
    }

    public function testSelectFailureNoneReplicating()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(false);

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->pool);
    }
}
