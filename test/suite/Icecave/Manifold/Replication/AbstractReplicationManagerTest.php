<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimeSpan\Duration;
use PHPUnit_Framework_TestCase;
use Phake;

class AbstractReplicationManagerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection1 = Phake::mock('PDO');
        $this->connection1->id = '1';
        $this->connection2 = Phake::mock('PDO');
        $this->connection2->id = '2';
        $this->connection3 = Phake::mock('PDO');
        $this->connection3->id = '3';
        $this->connection4 = Phake::mock('PDO');
        $this->connection4->id = '4';
        $this->connection5 = Phake::mock('PDO');
        $this->connection5->id = '5';
        $this->tree = new ReplicationTree($this->connection1);
        $this->tree->addSlave($this->connection1, $this->connection2);
        $this->tree->addSlave($this->connection2, $this->connection3);
        $this->tree->addSlave($this->connection2, $this->connection4);
        $this->clock = Phake::partialMock('Icecave\Chrono\Clock\SystemClock');
        $this->timer = Phake::mock('Icecave\Chrono\Timer\TimerInterface');
        $this->manager = Phake::partialMock(
            __NAMESPACE__ . '\AbstractReplicationManager',
            $this->tree,
            $this->clock,
            $this->timer
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->tree, $this->manager->tree());
        $this->assertSame($this->clock, $this->manager->clock());
        $this->assertSame($this->timer, $this->manager->timer());
    }

    public function testConstructorDefaults()
    {
        $this->manager = Phake::partialMock(__NAMESPACE__ . '\AbstractReplicationManager', $this->tree);

        $this->assertInstanceOf('Icecave\Chrono\Clock\SystemClock', $this->manager->clock());
        $this->assertInstanceOf('Icecave\Chrono\Timer\Timer', $this->manager->timer());
    }

    public function testDelay()
    {
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection1, $this->connection2)
            ->thenReturn(new Duration(111));
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection2, $this->connection3)
            ->thenReturn(new Duration(222));

        $this->assertEquals(new Duration(333), $this->manager->delay($this->connection3, null, $this->connection1));
        $this->assertEquals(
            new Duration(333),
            $this->manager->delay($this->connection3, new Duration(333), $this->connection1)
        );
        $this->assertEquals(new Duration(333), $this->manager->delay($this->connection3, 333, $this->connection1));
        $this->assertEquals(new Duration(222), $this->manager->delay($this->connection3, null, $this->connection2));
        $this->assertEquals(new Duration(111), $this->manager->delay($this->connection2, null, $this->connection1));
        $this->assertEquals(new Duration(333), $this->manager->delay($this->connection3));
        $this->assertNull($this->manager->delay($this->connection3, new Duration(110), $this->connection1));
        $this->assertNull($this->manager->delay($this->connection3, 110, $this->connection1));
        $this->assertNull($this->manager->delay($this->connection3, new Duration(110)));
        $this->assertNull($this->manager->delay($this->connection3, 110));
    }

    public function testDelayFailureNoPath()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotReplicatingException');
        $this->manager->delay($this->connection4, null, $this->connection3);
    }

    public function testDelayFailureNotReplicating()
    {
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection1, $this->connection2)
            ->thenReturn(new Duration(111));
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection2, $this->connection3)
            ->thenReturn(null);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotReplicatingException');
        $this->manager->delay($this->connection3, null, $this->connection1);
    }

    public function testDelayWithin()
    {
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection1, $this->connection2)
            ->thenReturn(new Duration(111));
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection2, $this->connection3)
            ->thenReturn(new Duration(222));

        $this->assertTrue($this->manager->delayWithin(new Duration(333), $this->connection3, $this->connection1));
        $this->assertTrue($this->manager->delayWithin(333, $this->connection3, $this->connection1));
        $this->assertFalse($this->manager->delayWithin(new Duration(110), $this->connection3, $this->connection1));
        $this->assertFalse($this->manager->delayWithin(110, $this->connection3, $this->connection1));
        $this->assertFalse($this->manager->delayWithin(new Duration(110), $this->connection3));
        $this->assertFalse($this->manager->delayWithin(110, $this->connection3));
    }

    public function testDelayWithinFailureNoPath()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotReplicatingException');
        $this->manager->delayWithin(111, $this->connection4, $this->connection3);
    }

    public function testDelayWithinFailureNotReplicating()
    {
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection1, $this->connection2)
            ->thenReturn(new Duration(111));
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection2, $this->connection3)
            ->thenReturn(null);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotReplicatingException');
        $this->manager->delayWithin(111, $this->connection3, $this->connection1);
    }

    public function testIsReplicating()
    {
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection1, $this->connection2)
            ->thenReturn(new Duration(111));
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection2, $this->connection3)
            ->thenReturn(new Duration(222));
        Phake::when($this->manager)
            ->amountBehindMaster($this->connection2, $this->connection4)
            ->thenReturn(null);

        $this->assertTrue($this->manager->isReplicating($this->connection2));
        $this->assertTrue($this->manager->isReplicating($this->connection3));
        $this->assertTrue($this->manager->isReplicating($this->connection3, $this->connection1));
        $this->assertFalse($this->manager->isReplicating($this->connection4));
        $this->assertFalse($this->manager->isReplicating($this->connection4, $this->connection3));
    }

    public function testWaitRoot()
    {
        $this->assertTrue($this->manager->wait($this->connection1));
        Phake::verify($this->manager, Phake::never())->doWait(Phake::anyParameters());
    }

    public function testWaitNoTimeout()
    {
        Phake::when($this->manager)->doWait($this->connection1, $this->connection2)
            ->thenReturn(true);
        Phake::when($this->manager)->doWait($this->connection2, $this->connection3)
            ->thenReturn(true)
            ->thenReturn(true)
            ->thenReturn(false);

        $this->assertTrue($this->manager->wait($this->connection3));
        $this->assertTrue($this->manager->wait($this->connection3, null, $this->connection1));
        $this->assertFalse($this->manager->wait($this->connection3));
    }

    public function testWaitWithTimeout()
    {
        Phake::when($this->timer)->elapsed()
            ->thenReturn(400.0)
            ->thenReturn(401.0)
            ->thenReturn(402.0)
            ->thenReturn(403.0)
            ->thenReturn(404.0)
            ->thenReturn(405.0)
            ->thenReturn(406.0)
            ->thenReturn(407.0)
            ->thenReturn(408.0)
            ->thenReturn(500.0);
        Phake::when($this->manager)->doWait($this->connection1, $this->connection2, $this->anything())
            ->thenReturn(true);
        Phake::when($this->manager)->doWait($this->connection2, $this->connection3, $this->anything())
            ->thenReturn(true)
            ->thenReturn(true)
            ->thenReturn(true)
            ->thenReturn(false)
            ->thenReturn(true);

        $this->assertTrue($this->manager->wait($this->connection3, new Duration(444)));
        $this->assertTrue($this->manager->wait($this->connection3, 444));
        $this->assertTrue($this->manager->wait($this->connection3, new Duration(444), $this->connection1));
        $this->assertFalse($this->manager->wait($this->connection3, new Duration(444)));
        $this->assertFalse($this->manager->wait($this->connection3, new Duration(444)));
        $timerReset = Phake::verify($this->timer, Phake::atLeast(1))->reset();
        $timerStart = Phake::verify($this->timer, Phake::atLeast(1))->start();
        Phake::inOrder(
            $timerReset,
            $timerStart,
            Phake::verify($this->manager)->doWait($this->connection1, $this->connection2, new Duration(44)),
            Phake::verify($this->manager)->doWait($this->connection2, $this->connection3, new Duration(43)),

            $timerReset,
            $timerStart,
            Phake::verify($this->manager)->doWait($this->connection1, $this->connection2, new Duration(42)),
            Phake::verify($this->manager)->doWait($this->connection2, $this->connection3, new Duration(41)),

            $timerReset,
            $timerStart,
            Phake::verify($this->manager)->doWait($this->connection1, $this->connection2, new Duration(40)),
            Phake::verify($this->manager)->doWait($this->connection2, $this->connection3, new Duration(39)),

            $timerReset,
            $timerStart,
            Phake::verify($this->manager)->doWait($this->connection1, $this->connection2, new Duration(38)),
            Phake::verify($this->manager)->doWait($this->connection2, $this->connection3, new Duration(37)),

            $timerReset,
            $timerStart,
            Phake::verify($this->manager)->doWait($this->connection1, $this->connection2, new Duration(36))
        );
        Phake::verify($this->manager, Phake::never())->doWait($this->connection2, $this->connection3, new Duration(35));
    }

    public function testWaitFailureNoPath()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotReplicatingException');
        $this->manager->wait($this->connection4, null, $this->connection3);
    }
}
