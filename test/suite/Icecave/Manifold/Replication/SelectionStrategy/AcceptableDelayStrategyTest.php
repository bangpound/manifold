<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Icecave\Manifold\Replication\SelectionStrategy\AcceptableDelayStrategy
 * @covers \Icecave\Manifold\Replication\SelectionStrategy\AbstractSelectionStrategy
 */
class AcceptableDelayStrategyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->threshold = new Duration(222);
        $this->clock = Phake::partialMock('Icecave\Chrono\Clock\SystemClock');
        $this->strategy = new AcceptableDelayStrategy($this->threshold, $this->clock);

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
        $this->assertSame($this->threshold, $this->strategy->threshold());
        $this->assertSame($this->clock, $this->strategy->clock());
    }

    public function testConstructorDefaults()
    {
        $this->strategy = new AcceptableDelayStrategy;

        $this->assertSame(3, $this->strategy->threshold()->totalSeconds());
        $this->assertInstanceOf('Icecave\Chrono\Clock\SystemClock', $this->strategy->clock());
    }

    public function testConstructorNormalization()
    {
        $this->strategy = new AcceptableDelayStrategy(111);

        $this->assertSame(111, $this->strategy->threshold()->totalSeconds());
    }

    public function testSelect()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool));
        Phake::verify($this->manager, Phake::never())->delay($this->connectionC);
    }

    public function testSelectLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection from pool {pool} with replication delay less than the threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Replication delay of {delay} is greater than the threshold {threshold}.',
                array('connection' => 'A', 'pool' => 'pool', 'delay' => 'PT5M33S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from pool {pool}. ' .
                    'Replication delay of {delay} is within the threshold {threshold}.',
                array('connection' => 'B', 'pool' => 'pool', 'delay' => 'PT1M51S', 'threshold' => 'PT3M42S')
            )
        );
        Phake::verify($this->manager, Phake::never())->delay($this->connectionC);
    }

    public function testSelectFailureThreshold()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(444));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(555));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->pool);
    }

    public function testSelectFailureThresholdLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(444));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(555));

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->pool, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection from pool {pool} with replication delay less than the threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Replication delay of {delay} is greater than the threshold {threshold}.',
                array('connection' => 'A', 'pool' => 'pool', 'delay' => 'PT7M24S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Replication delay of {delay} is greater than the threshold {threshold}.',
                array('connection' => 'B', 'pool' => 'pool', 'delay' => 'PT5M33S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Replication delay of {delay} is greater than the threshold {threshold}.',
                array('connection' => 'C', 'pool' => 'pool', 'delay' => 'PT9M15S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in pool {pool}. ' .
                    'No connection found with replication delay within the threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT3M42S')
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
                'Selecting connection from pool {pool} with replication delay less than the threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT3M42S')
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
                    'No connection found with replication delay within the threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT3M42S')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }
}
