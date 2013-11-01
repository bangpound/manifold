<?php
namespace Icecave\Manifold\Replication;

use Icecave\Collections\Set;
use InvalidArgumentException;
use Phake;
use PHPUnit_Framework_TestCase;

class ReplicationTreeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection1 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connection2 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connection3 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connection4 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connection5 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');

        $this->tree = new ReplicationTree($this->connection1);
        $this->tree->addSlave($this->connection1, $this->connection2);
        $this->tree->addSlave($this->connection2, $this->connection3);
        $this->tree->addSlave($this->connection2, $this->connection4);
    }

    public function testReplicationRoot()
    {
        $this->assertSame($this->connection1, $this->tree->replicationRoot());
    }

    public function testHasConnection()
    {
        $this->assertTrue($this->tree->hasConnection($this->connection1));
        $this->assertTrue($this->tree->hasConnection($this->connection2));
        $this->assertTrue($this->tree->hasConnection($this->connection3));
        $this->assertTrue($this->tree->hasConnection($this->connection4));
        $this->assertFalse($this->tree->hasConnection($this->connection5));
    }

    public function testIsRoot()
    {
        $this->assertTrue($this->tree->isRoot($this->connection1));
        $this->assertFalse($this->tree->isRoot($this->connection2));
        $this->assertFalse($this->tree->isRoot($this->connection3));
        $this->assertFalse($this->tree->isRoot($this->connection4));
    }

    public function testIsRootWithUnknownConnection()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isRoot($this->connection5);
    }

    public function testIsLeaf()
    {
        $this->assertFalse($this->tree->isLeaf($this->connection1));
        $this->assertFalse($this->tree->isLeaf($this->connection2));
        $this->assertTrue($this->tree->isLeaf($this->connection3));
        $this->assertTrue($this->tree->isLeaf($this->connection4));
    }

    public function testIsLeafWithUnknownConnection()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isLeaf($this->connection5);
    }

    public function testIsMaster()
    {
        $this->assertTrue($this->tree->isMaster($this->connection1));
        $this->assertTrue($this->tree->isMaster($this->connection2));
        $this->assertFalse($this->tree->isMaster($this->connection3));
        $this->assertFalse($this->tree->isMaster($this->connection4));
    }

    public function testIsMasterWithUnknownConnection()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isMaster($this->connection5);
    }

    public function testIsSlave()
    {
        $this->assertFalse($this->tree->isSlave($this->connection1));
        $this->assertTrue($this->tree->isSlave($this->connection2));
        $this->assertTrue($this->tree->isSlave($this->connection3));
        $this->assertTrue($this->tree->isSlave($this->connection4));
    }

    public function testIsSlaveWithUnknownConnection()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isSlave($this->connection5);
    }

    public function testMasterOf()
    {
        $this->assertNull($this->tree->masterOf($this->connection1));
        $this->assertSame($this->connection1, $this->tree->masterOf($this->connection2));
        $this->assertSame($this->connection2, $this->tree->masterOf($this->connection3));
        $this->assertSame($this->connection2, $this->tree->masterOf($this->connection4));
    }

    public function testMasterOfWithUnknownConnection()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->masterOf($this->connection5);
    }

    public function testSlavesOf()
    {
        $this->assertTrue(Set::create($this->connection2)->isEqualSet($this->tree->slavesOf($this->connection1)));
        $this->assertTrue(Set::create($this->connection3, $this->connection4)->isEqualSet($this->tree->slavesOf($this->connection2)));
        $this->assertTrue($this->tree->slavesOf($this->connection3)->isEmpty());
        $this->assertTrue($this->tree->slavesOf($this->connection4)->isEmpty());
    }

    public function testSlavesOfWithUnknownConncetion()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->slavesOf($this->connection5);
    }

    public function testIsReplicatingTo()
    {
        $this->assertSame(false, $this->tree->isReplicatingTo($this->connection1, $this->connection1));
        $this->assertSame(true,  $this->tree->isReplicatingTo($this->connection2, $this->connection1));
        $this->assertSame(true,  $this->tree->isReplicatingTo($this->connection3, $this->connection1));
        $this->assertSame(true,  $this->tree->isReplicatingTo($this->connection4, $this->connection1));

        $this->assertSame(false, $this->tree->isReplicatingTo($this->connection1, $this->connection2));
        $this->assertSame(false, $this->tree->isReplicatingTo($this->connection2, $this->connection2));
        $this->assertSame(true,  $this->tree->isReplicatingTo($this->connection3, $this->connection2));
        $this->assertSame(true,  $this->tree->isReplicatingTo($this->connection4, $this->connection2));
    }

    public function testIsReplicatingWithUnknownMaster()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isReplicatingTo($this->connection1, $this->connection5);
    }

    public function testIsReplicatingWithUnknownSlave()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isReplicatingTo($this->connection1, $this->connection5);
    }

    public function testIsMasterOf()
    {
        $this->assertSame(false, $this->tree->isMasterOf($this->connection1, $this->connection1));
        $this->assertSame(true,  $this->tree->isMasterOf($this->connection1, $this->connection2));
        $this->assertSame(false, $this->tree->isMasterOf($this->connection1, $this->connection3));
        $this->assertSame(false, $this->tree->isMasterOf($this->connection1, $this->connection4));

        $this->assertSame(false, $this->tree->isMasterOf($this->connection2, $this->connection1));
        $this->assertSame(false, $this->tree->isMasterOf($this->connection2, $this->connection2));
        $this->assertSame(true,  $this->tree->isMasterOf($this->connection2, $this->connection3));
        $this->assertSame(true,  $this->tree->isMasterOf($this->connection2, $this->connection4));
    }

    public function testIsMasterOfWithUnknownMaster()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isMasterOf($this->connection5, $this->connection1);
    }

    public function testIsMasterOfWithUnknownSlave()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->isMasterOf($this->connection1, $this->connection5);
    }

    public function testCountHops()
    {
        $this->assertSame(0,    $this->tree->countHops($this->connection1, $this->connection1));
        $this->assertSame(1,    $this->tree->countHops($this->connection2, $this->connection1));
        $this->assertSame(2,    $this->tree->countHops($this->connection3, $this->connection1));
        $this->assertSame(2,    $this->tree->countHops($this->connection4, $this->connection1));
        $this->assertSame(0,    $this->tree->countHops($this->connection1));
        $this->assertSame(1,    $this->tree->countHops($this->connection2));
        $this->assertSame(2,    $this->tree->countHops($this->connection3));
        $this->assertSame(2,    $this->tree->countHops($this->connection4));

        $this->assertSame(null, $this->tree->countHops($this->connection1, $this->connection2));
        $this->assertSame(0,    $this->tree->countHops($this->connection2, $this->connection2));
        $this->assertSame(1,    $this->tree->countHops($this->connection3, $this->connection2));
        $this->assertSame(1,    $this->tree->countHops($this->connection4, $this->connection2));
    }

    public function testCountHopsWithUnknownMaster()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->countHops($this->connection1, $this->connection5);
    }

    public function testCountHopsWithUnknownSlave()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->countHops($this->connection5, $this->connection1);
    }

    public function testReplicationPath()
    {
        $this->assertEquals(array(), $this->tree->replicationPath($this->connection1, $this->connection1));
        $this->assertEquals(array(), $this->tree->replicationPath($this->connection1));

        $this->assertEquals(
            array(
                array($this->connection1, $this->connection2),
            ),
            $this->tree->replicationPath($this->connection2, $this->connection1)
        );
        $this->assertEquals(
            array(
                array($this->connection1, $this->connection2),
            ),
            $this->tree->replicationPath($this->connection2)
        );

        $this->assertEquals(
            array(
                array($this->connection1, $this->connection2),
                array($this->connection2, $this->connection3),
            ),
            $this->tree->replicationPath($this->connection3, $this->connection1)
        );
        $this->assertEquals(
            array(
                array($this->connection1, $this->connection2),
                array($this->connection2, $this->connection3),
            ),
            $this->tree->replicationPath($this->connection3)
        );

        $this->assertEquals(
            array(
                array($this->connection1, $this->connection2),
                array($this->connection2, $this->connection4),
            ),
            $this->tree->replicationPath($this->connection4, $this->connection1)
        );
        $this->assertEquals(
            array(
                array($this->connection1, $this->connection2),
                array($this->connection2, $this->connection4),
            ),
            $this->tree->replicationPath($this->connection4)
        );

        $this->assertNull($this->tree->replicationPath($this->connection1, $this->connection2));

        $this->assertEquals(array(), $this->tree->replicationPath($this->connection2, $this->connection2));

        $this->assertEquals(
            array(
                array($this->connection2, $this->connection3),
            ),
            $this->tree->replicationPath($this->connection3, $this->connection2)
        );

        $this->assertEquals(
            array(
                array($this->connection2, $this->connection4)
            ),
            $this->tree->replicationPath($this->connection4, $this->connection2)
        );
    }

    public function testReplicationPathWithUnknownMaster()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->replicationPath($this->connection1, $this->connection5);
    }

    public function testReplicationPathWithUnknownSlave()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->replicationPath($this->connection5, $this->connection1);
    }

    public function testAddSlaveWithUnknownMaster()
    {
        $tree = new ReplicationTree($this->connection1);

        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $tree->addSlave($this->connection2, $this->connection3);
    }

    public function testRemoveSlave()
    {
        $this->tree->removeSlave($this->connection2);

        $this->assertTrue($this->tree->hasConnection($this->connection1));
        $this->assertFalse($this->tree->hasConnection($this->connection2));
        $this->assertFalse($this->tree->hasConnection($this->connection3));
        $this->assertFalse($this->tree->hasConnection($this->connection4));
        $this->assertFalse($this->tree->hasConnection($this->connection5));
    }

    public function testRemoveSlaveWithReplicationRoot()
    {
        $this->setExpectedException(InvalidArgumentException::CLASS, 'The root connection can not be removed from the tree.');
        $this->tree->removeSlave($this->connection1);
    }

    public function testRemoveSlaveWithUnknownSlave()
    {
        $this->setExpectedException(Exception\UnknownConnectionException::CLASS);
        $this->tree->removeSlave($this->connection5);
    }
}
