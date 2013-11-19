<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\Container\ConnectionPool;
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
        $this->container = new ConnectionPool(
            'container',
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
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(111));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->container));
        Phake::verify($this->manager, Phake::never())->delay($this->connectionC, $this->threshold);
    }

    public function testSelectLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(111));

        $this->assertSame($this->connectionB, $this->strategy->select($this->manager, $this->container, $this->logger));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection from container {container} with replication delay less than the threshold ' .
                '{threshold}.',
                array('container' => 'container', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'Replication delay is at least {delay}, and is greater than the threshold {threshold}.',
                array('connection' => 'A', 'container' => 'container', 'delay' => 'PT5M33S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} selected from container {container}. ' .
                    'Replication delay of {delay} is within the threshold {threshold}.',
                array('connection' => 'B', 'container' => 'container', 'delay' => 'PT1M51S', 'threshold' => 'PT3M42S')
            )
        );
        Phake::verify($this->manager, Phake::never())->delay($this->connectionC, $this->threshold);
    }

    public function testSelectFailureThreshold()
    {
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(444));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionC, $this->threshold)->thenReturn(new Duration(555));

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        $this->strategy->select($this->manager, $this->container);
    }

    public function testSelectFailureThresholdLogging()
    {
        Phake::when($this->manager)->delay($this->connectionA, $this->threshold)->thenReturn(new Duration(444));
        Phake::when($this->manager)->delay($this->connectionB, $this->threshold)->thenReturn(new Duration(333));
        Phake::when($this->manager)->delay($this->connectionC, $this->threshold)->thenReturn(new Duration(555));

        $caught = null;
        try {
            $this->strategy->select($this->manager, $this->container, $this->logger);
        } catch (NoConnectionAvailableException $caught) {}
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Selecting connection from container {container} with replication delay less than the threshold ' .
                '{threshold}.',
                array('container' => 'container', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'Replication delay is at least {delay}, and is greater than the threshold {threshold}.',
                array('connection' => 'A', 'container' => 'container', 'delay' => 'PT7M24S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'Replication delay is at least {delay}, and is greater than the threshold {threshold}.',
                array('connection' => 'B', 'container' => 'container', 'delay' => 'PT5M33S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->debug(
                'Connection {connection} not selected from container {container}. ' .
                    'Replication delay is at least {delay}, and is greater than the threshold {threshold}.',
                array('connection' => 'C', 'container' => 'container', 'delay' => 'PT9M15S', 'threshold' => 'PT3M42S')
            ),
            Phake::verify($this->logger)->warning(
                'No acceptable connection found in container {container}. ' .
                    'No connection found with replication delay within the threshold {threshold}.',
                array('container' => 'container', 'threshold' => 'PT3M42S')
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
                'Selecting connection from container {container} with replication delay less than the threshold ' .
                '{threshold}.',
                array('container' => 'container', 'threshold' => 'PT3M42S')
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
                array('container' => 'container', 'threshold' => 'PT3M42S')
            )
        );
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NoConnectionAvailableException');
        if (null !== $caught) {
            throw $caught;
        }
    }

    public function testString()
    {
        $expected = "Any replicating connection with a delay less than 'PT3M42S'.";

        $this->assertSame($expected, $this->strategy->string());
        $this->assertSame($expected, strval($this->strategy));
    }
}
