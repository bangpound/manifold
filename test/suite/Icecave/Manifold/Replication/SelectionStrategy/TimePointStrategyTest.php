<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\DateTime;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Icecave\Manifold\Replication\SelectionStrategy\TimePointStrategy
 * @covers \Icecave\Manifold\Replication\SelectionStrategy\AbstractSelectionStrategy
 */
class TimePointStrategyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->timePoint = new DateTime(2001, 1, 1, 12, 0, 1);
        $this->clock = Phake::partialMock('Icecave\Chrono\Clock\SystemClock');
        $this->strategy = new TimePointStrategy($this->timePoint, $this->clock);

        $this->manager = Phake::mock('Icecave\Manifold\Replication\ReplicationManagerInterface');
        $this->connectionA = Phake::mock('PDO');
        $this->connectionA->id = 'A';
        $this->connectionB = Phake::mock('PDO');
        $this->connectionB->id = 'B';
        $this->connectionC = Phake::mock('PDO');
        $this->connectionC->id = 'C';
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
        $this->assertSame($this->timePoint, $this->strategy->timePoint());
        $this->assertSame($this->clock, $this->strategy->clock());
    }

    public function testConstructorDefaultTimePoint()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 0));
        $this->strategy = new TimePointStrategy(null, $this->clock);

        $this->assertSame(978350400, $this->strategy->timePoint()->unixTime());
    }

    public function testConstructorDefaultClock()
    {
        $this->strategy = new TimePointStrategy;

        $this->assertInstanceOf('Icecave\Chrono\Clock\SystemClock', $this->strategy->clock());
    }

    public function testConstructorNormalization()
    {
        $this->strategy = new TimePointStrategy(111);

        $this->assertSame(111, $this->strategy->timePoint()->unixTime());
    }

    public function testSelect()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 3));
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(2));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(1));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool));
        Phake::verify($this->manager, Phake::never())->isReplicating($this->connectionC);
    }

    public function testSelectFailureThreshold()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 2));
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(4));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(2));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->pool);
    }

    public function testSelectFailureNoneReplicating()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(false);

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->pool);
    }
}
