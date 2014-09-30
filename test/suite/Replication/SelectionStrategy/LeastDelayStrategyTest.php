<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Manifold\Connection\Container\ConnectionPool;
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
        $this->container = new ConnectionPool(
            'container',
            array(
                $this->connectionA,
                $this->connectionB,
                $this->connectionC,
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
        $this->strategy = new LeastDelayStrategy();

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
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC, $this->threshold)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->container));
    }

    public function testSelectLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC, $this->threshold)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->container, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from container {container}, where replication ' .
                    'delay is less than threshold {threshold}.',
                array('container' => 'container', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from container {container} has a replication delay of {delay}.',
                array('connection' => 'A', 'container' => 'container', 'delay' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from container {container} has a replication delay of {delay}.',
                array('connection' => 'B', 'container' => 'container', 'delay' => 'PT1M51S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from container {container} has a replication delay of {delay}.',
                array('connection' => 'C', 'container' => 'container', 'delay' => 'PT5M33S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from container {container}. ' .
                    'Connection has the least replication delay of all suitable candidates.',
                array('connection' => 'B', 'container' => 'container')
            )
        );
    }

    public function testSelectNoThreshold()
    {
        $this->strategy = new LeastDelayStrategy(null, $this->clock);
        Phake::when($this->manager)->delay($this->connectionA, null)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB, null)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC, null)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->container));
    }

    public function testSelectNoThresholdLogging()
    {
        $this->strategy = new LeastDelayStrategy(null, $this->clock);
        Phake::when($this->manager)->delay($this->connectionA, null)->thenReturn(new Duration(222));
        Phake::when($this->manager)->delay($this->connectionB, null)->thenReturn(new Duration(111));
        Phake::when($this->manager)->delay($this->connectionC, null)->thenReturn(new Duration(333));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->container, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from container {container}.',
                array('container' => 'container')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from container {container} has a replication delay of {delay}.',
                array('connection' => 'A', 'container' => 'container', 'delay' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from container {container} has a replication delay of {delay}.',
                array('connection' => 'B', 'container' => 'container', 'delay' => 'PT1M51S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} from container {container} has a replication delay of {delay}.',
                array('connection' => 'C', 'container' => 'container', 'delay' => 'PT5M33S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from container {container}. ' .
                    'Connection has the least replication delay of all suitable candidates.',
                array('connection' => 'B', 'container' => 'container')
            )
        );
    }

    public function testSelectFailureThreshold()
    {
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(666));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(555));
        Phake::when($this->manager)->delay($this->connectionC, $this->threshold)->thenReturn(new Duration(777));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->container);
    }

    public function testSelectFailureThresholdLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(666));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(555));
        Phake::when($this->manager)->delay($this->connectionC, $this->threshold)->thenReturn(new Duration(777));

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->container, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from container {container}, where replication ' .
                    'delay is less than threshold {threshold}.',
                array('container' => 'container', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'Replication delay is at least {delay}, and is greater than the threshold {threshold}.',
                array('connection' => 'A', 'container' => 'container', 'delay' => 'PT11M6S', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'Replication delay is at least {delay}, and is greater than the threshold {threshold}.',
                array('connection' => 'B', 'container' => 'container', 'delay' => 'PT9M15S', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'Replication delay is at least {delay}, and is greater than the threshold {threshold}.',
                array('connection' => 'C', 'container' => 'container', 'delay' => 'PT12M57S', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in container {container}. ' .
                    'No connection found with replication delay within the threshold {threshold}.',
                array('container' => 'container', 'threshold' => 'PT7M24S')
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
        $this->strategy->select($this->manager, $this->container);
    }

    public function testSelectFailureNoneReplicatingLogging()
    {
        Phake::when($this->manager)->delay(Phake::anyParameters())->thenReturn(null);

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->container, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from container {container}, where replication ' .
                    'delay is less than threshold {threshold}.',
                array('container' => 'container', 'threshold' => 'PT7M24S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'The connection is not replicating.',
                array('connection' => 'A', 'container' => 'container')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'The connection is not replicating.',
                array('connection' => 'B', 'container' => 'container')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'The connection is not replicating.',
                array('connection' => 'C', 'container' => 'container')
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in container {container}. ' .
                    'No connection found with replication delay within the threshold {threshold}.',
                array('container' => 'container', 'threshold' => 'PT7M24S')
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
            $this->strategy->select($this->manager, $this->container, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection with least replication delay from container {container}.',
                array('container' => 'container')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'The connection is not replicating.',
                array('connection' => 'A', 'container' => 'container')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'The connection is not replicating.',
                array('connection' => 'B', 'container' => 'container')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'The connection is not replicating.',
                array('connection' => 'C', 'container' => 'container')
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in container {container}.',
                array('container' => 'container')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }

    public function testString()
    {
        $expected = "The connection with the least replication delay, but also less than 'PT7M24S'.";

        $this->assertSame($expected, $this->strategy->string());
        $this->assertSame($expected, strval($this->strategy));
    }

    public function testStringWithNoThreshold()
    {
        $this->strategy = new LeastDelayStrategy();
        $expected = "The connection with the least replication delay.";

        $this->assertSame($expected, $this->strategy->string());
        $this->assertSame($expected, strval($this->strategy));
    }
}
