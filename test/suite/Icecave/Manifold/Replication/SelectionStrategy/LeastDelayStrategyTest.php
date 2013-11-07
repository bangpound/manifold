<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
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
        $this->threshold = new Duration(444);
        $this->clock = Phake::partialMock('Icecave\Chrono\Clock\SystemClock');
        $this->strategy = new LeastDelayStrategy($this->threshold, $this->clock);

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
        $this->strategy = new LeastDelayStrategy;

        $this->assertNull($this->strategy->threshold());
        $this->assertInstanceOf('Icecave\Chrono\Clock\SystemClock', $this->strategy->clock());
    }

    public function testConstructorNormalization()
    {
        $this->strategy = new LeastDelayStrategy(111);

        $this->assertSame(111, $this->strategy->threshold()->totalSeconds());
    }

    public function testSelect()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool));
    }

    public function testSelectLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from pool {pool}, where replication delay is ' .
                    'less than threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from pool {pool} has a replication delay of {delay}.',
                array('connection' => 'A', 'pool' => 'pool', 'delay' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from pool {pool} has a replication delay of {delay}.',
                array('connection' => 'B', 'pool' => 'pool', 'delay' => 'PT1M51S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from pool {pool} has a replication delay of {delay}.',
                array('connection' => 'C', 'pool' => 'pool', 'delay' => 'PT5M33S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from pool {pool}. ' .
                    'Connection has the least replication delay of all suitable candidates.',
                array('connection' => 'B', 'pool' => 'pool')
            )
        );
    }

    public function testSelectNoThreshold()
    {
        $this->strategy = new LeastDelayStrategy(null, $this->clock);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool));
    }

    public function testSelectNoThresholdLogging()
    {
        $this->strategy = new LeastDelayStrategy(null, $this->clock);
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->pool, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from pool {pool}.',
                array('pool' => 'pool')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from pool {pool} has a replication delay of {delay}.',
                array('connection' => 'A', 'pool' => 'pool', 'delay' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from pool {pool} has a replication delay of {delay}.',
                array('connection' => 'B', 'pool' => 'pool', 'delay' => 'PT1M51S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from pool {pool} has a replication delay of {delay}.',
                array('connection' => 'C', 'pool' => 'pool', 'delay' => 'PT5M33S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from pool {pool}. ' .
                    'Connection has the least replication delay of all suitable candidates.',
                array('connection' => 'B', 'pool' => 'pool')
            )
        );
    }

    public function testSelectFailureThreshold()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(666));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(555));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(777));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->pool);
    }

    public function testSelectFailureThresholdLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA)->thenReturn(new Duration(666));
        Phake::when($this->manager)->delay($this->connectionB)->thenReturn(new Duration(555));
        Phake::when($this->manager)->delay($this->connectionC)->thenReturn(new Duration(777));

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->pool, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from pool {pool}, where replication delay is ' .
                    'less than threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Replication delay of {delay} is greater than the threshold {threshold}.',
                array('connection' => 'A', 'pool' => 'pool', 'delay' => 'PT11M6S', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Replication delay of {delay} is greater than the threshold {threshold}.',
                array('connection' => 'B', 'pool' => 'pool', 'delay' => 'PT9M15S', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from pool {pool}. ' .
                    'Replication delay of {delay} is greater than the threshold {threshold}.',
                array('connection' => 'C', 'pool' => 'pool', 'delay' => 'PT12M57S', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in pool {pool}. ' .
                    'No connection found with replication delay within the threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT7M24S')
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
                'Selecting connection with least replication delay from pool {pool}, where replication delay is ' .
                    'less than threshold {threshold}.',
                array('pool' => 'pool', 'threshold' => 'PT7M24S')
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
                array('pool' => 'pool', 'threshold' => 'PT7M24S')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }

    public function testSelectFailureNoneReplicatingNoThresholdLogging()
    {
        $this->strategy = new LeastDelayStrategy(null, $this->clock);
        Phake::when($this->manager)->delay(Phake::anyParameters())->thenReturn(null);

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->pool, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from pool {pool}.',
                array('pool' => 'pool')
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
                'No acceptable connection found in pool {pool}.',
                array('pool' => 'pool')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }
}
