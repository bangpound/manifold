<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\DateTime;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\LazyPdoConnection;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use PHPUnit_Framework_TestCase;
use Phake;

class ConnectionSelectorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->manager = Phake::mock(__NAMESPACE__ . '\ReplicationManagerInterface');
        $this->clock = Phake::partialMock('Icecave\Chrono\Clock\SystemClock');
        $this->selector = new ConnectionSelector($this->manager, $this->clock);

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
        $this->assertSame($this->manager, $this->selector->manager());
        $this->assertSame($this->clock, $this->selector->clock());
    }

    public function testConstructorDefaults()
    {
        $this->selector = new ConnectionSelector($this->manager);

        $this->assertInstanceOf('Icecave\Chrono\Clock\SystemClock', $this->selector->clock());
    }

    public function testSelect()
    {
        Phake::when($this->manager)->isReplicating($this->connectionA)->thenReturn(false);
        Phake::when($this->manager)->isReplicating($this->connectionB)->thenReturn(true);

        $this->assertSame($this->connectionB, $this->selector->select($this->pool));
        Phake::verify($this->manager, Phake::never())->isReplicating($this->connectionC);
    }

    public function testSelectFailureNoneReplicating()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(false);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NoConnectionAvailableException');
        $this->selector->select($this->pool);
    }

    public function testSelectByLeastDelay()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));
        $threshold = new Duration(444);

        $this->assertSame($this->connectionB, $this->selector->selectByLeastDelay($this->pool, $threshold));
        $this->assertSame($this->connectionB, $this->selector->selectByLeastDelay($this->pool, 444));
        $this->assertSame($this->connectionB, $this->selector->selectByLeastDelay($this->pool, null));
    }

    public function testSelectByLeastDelayFailureThreshold()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));
        $threshold = new Duration(1);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NoConnectionAvailableException');
        $this->selector->selectByLeastDelay($this->pool, $threshold);
    }

    public function testSelectByLeastDelayFailureNoneReplicating()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(false);
        $threshold = new Duration(444);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NoConnectionAvailableException');
        $this->selector->selectByLeastDelay($this->pool, $threshold);
    }

    public function testSelectByAcceptableDelay()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        $threshold = new Duration(222);

        $this->assertSame($this->connectionB, $this->selector->selectByAcceptableDelay($this->pool, $threshold));
        $this->assertSame($this->connectionB, $this->selector->selectByAcceptableDelay($this->pool, 222));
        Phake::verify($this->manager, Phake::never())->isReplicating($this->connectionC);
    }

    public function testSelectByAcceptableDelayFailureThreshold()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));
        $threshold = new Duration(1);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NoConnectionAvailableException');
        $this->selector->selectByAcceptableDelay($this->pool, $threshold);
    }

    public function testSelectByAcceptableDelayFailureNoneReplicating()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(false);
        $threshold = new Duration(444);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NoConnectionAvailableException');
        $this->selector->selectByAcceptableDelay($this->pool, $threshold);
    }

    public function testSelectByTime()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 0));
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(2));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(1));
        $timePoint = new DateTime(2001, 1, 1, 11, 59, 58);

        $this->assertSame($this->connectionB, $this->selector->selectByTime($this->pool, $timePoint));
        $this->assertSame($this->connectionB, $this->selector->selectByTime($this->pool, $timePoint->unixTime()));
        Phake::verify($this->manager, Phake::never())->isReplicating($this->connectionC);
    }

    public function testSelectByTimeFailureThreshold()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 0));
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(2));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(1));
        $timePoint = new DateTime(2001, 1, 1, 12, 0, 0);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NoConnectionAvailableException');
        $this->selector->selectByTime($this->pool, $timePoint);
    }

    public function testSelectByTimeFailureNoneReplicating()
    {
        Phake::when($this->manager)->isReplicating(Phake::anyParameters())->thenReturn(false);
        $timePoint = new DateTime(2001, 1, 1, 12, 0, 0);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NoConnectionAvailableException');
        $this->selector->selectByTime($this->pool, $timePoint);
    }
}
