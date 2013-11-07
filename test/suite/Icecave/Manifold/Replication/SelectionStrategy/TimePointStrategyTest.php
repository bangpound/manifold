<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\DateTime;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
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
        $this->connectionA = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionA)->name()->thenReturn('A');
        $this->connectionB = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionB)->name()->thenReturn('B');
        $this->connectionC = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionC)->name()->thenReturn('C');
        $this->pool = new ConnectionPool(
            'pool',
            new Vector(
                array(
                    $this->connectionA,
                    $this->connectionB,
                    $this->connectionC,
                )
            )
        );

        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
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
        $delayThreshold = new Duration(2);
        Phake::when($this->manager)->delay($this->connectionA, $delayThreshold)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionB, $delayThreshold)->thenReturn(new Duration(2));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool));
        Phake::verify($this->manager, Phake::never())->delay($this->connectionC, $delayThreshold);
    }

    public function testSelectLogging()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 3));
        $delayThreshold = new Duration(2);
        Phake::when($this->manager)->delay($this->connectionA, $delayThreshold)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionB, $delayThreshold)->thenReturn(new Duration(2));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection from pool {pool} with connection time of at least {timePoint}.',
                array('pool' => 'pool', 'timePoint' => '2001-01-01T12:00:01+00:00')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Connection time is no more than {connectionTime}, and is less than {timePoint}.',
                array(
                    'connection' => 'A',
                    'pool' => 'pool',
                    'connectionTime' => '2001-01-01T12:00:00+00:00',
                    'timePoint' => '2001-01-01T12:00:01+00:00',
                )
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from pool {pool}. ' .
                    'Connection time of {connectionTime} is at least {timePoint}.',
                array(
                    'connection' => 'B',
                    'pool' => 'pool',
                    'connectionTime' => '2001-01-01T12:00:01+00:00',
                    'timePoint' => '2001-01-01T12:00:01+00:00',
                )
            )
        );
        Phake::verify($this->manager, Phake::never())->delay($this->connectionC, $delayThreshold);
    }

    public function testSelectFailureThreshold()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 2));
        $delayThreshold = new Duration(1);
        Phake::when($this->manager)->delay($this->connectionA, $delayThreshold)->thenReturn(new Duration(4));
        Phake::when($this->manager)->delay($this->connectionB, $delayThreshold)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionC, $delayThreshold)->thenReturn(new Duration(2));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->pool);
    }

    public function testSelectFailureThresholdLogging()
    {
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 2));
        $delayThreshold = new Duration(1);
        Phake::when($this->manager)->delay($this->connectionA, $delayThreshold)->thenReturn(new Duration(4));
        Phake::when($this->manager)->delay($this->connectionB, $delayThreshold)->thenReturn(new Duration(3));
        Phake::when($this->manager)->delay($this->connectionC, $delayThreshold)->thenReturn(new Duration(2));

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->pool, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection from pool {pool} with connection time of at least {timePoint}.',
                array('pool' => 'pool', 'timePoint' => '2001-01-01T12:00:01+00:00')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Connection time is no more than {connectionTime}, and is less than {timePoint}.',
                array(
                    'connection' => 'A',
                    'pool' => 'pool',
                    'connectionTime' => '2001-01-01T11:59:58+00:00',
                    'timePoint' => '2001-01-01T12:00:01+00:00',
                )
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Connection time is no more than {connectionTime}, and is less than {timePoint}.',
                array(
                    'connection' => 'B',
                    'pool' => 'pool',
                    'connectionTime' => '2001-01-01T11:59:59+00:00',
                    'timePoint' => '2001-01-01T12:00:01+00:00',
                )
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Connection time is no more than {connectionTime}, and is less than {timePoint}.',
                array(
                    'connection' => 'C',
                    'pool' => 'pool',
                    'connectionTime' => '2001-01-01T12:00:00+00:00',
                    'timePoint' => '2001-01-01T12:00:01+00:00',
                )
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in pool {pool}. ' .
                    'No connection found with connection time of at least {timePoint}.',
                array('pool' => 'pool', 'timePoint' => '2001-01-01T12:00:01+00:00')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }

    public function testSelectFailureNoneReplicating()
    {
        Phake::when($this->manager)->delay(Phake::anyParameters())->thenReturn(null);

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->pool);
    }

    public function testSelectFailureNoneReplicatingLogging()
    {
        Phake::when($this->manager)->delay(Phake::anyParameters())->thenReturn(null);

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->pool, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection from pool {pool} with connection time of at least {timePoint}.',
                array('pool' => 'pool', 'timePoint' => '2001-01-01T12:00:01+00:00')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'The connection is not replicating.',
                array('connection' => 'A', 'pool' => 'pool')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'The connection is not replicating.',
                array('connection' => 'B', 'pool' => 'pool')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'The connection is not replicating.',
                array('connection' => 'C', 'pool' => 'pool')
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in pool {pool}. ' .
                    'No connection found with connection time of at least {timePoint}.',
                array('pool' => 'pool', 'timePoint' => '2001-01-01T12:00:01+00:00')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }

    public function testSelectFailureTimePointInFuture()
    {
        $this->timePoint = new DateTime(2010, 1, 1, 12, 0, 0);
        $this->strategy = new TimePointStrategy($this->timePoint, $this->clock);
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 0));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->pool);
    }

    public function testSelectFailureTimePointInFutureLogging()
    {
        $this->timePoint = new DateTime(2010, 1, 1, 12, 0, 0);
        $this->strategy = new TimePointStrategy($this->timePoint, $this->clock);
        Phake::when($this->clock)->localDateTime()->thenReturn(new DateTime(2001, 1, 1, 12, 0, 0));

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->pool, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in pool {pool}. Desired time point {timePoint} is in the future.',
                array('pool' => 'pool', 'timePoint' => '2010-01-01T12:00:00+00:00')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }
}
