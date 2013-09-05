<?php
namespace Icecave\Manifold\Replication;

use Icecave\Collections\Set;
use Icecave\Manifold\Exception\UnknownDatabaseException;
use InvalidArgumentException;
use PDO;
use Phake;
use PHPUnit_Framework_TestCase;

class ReplicationManagerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection1 = Phake::mock('PDO');
        $this->connection2 = Phake::mock('PDO');
        $this->connection3 = Phake::mock('PDO');
        $this->connection4 = Phake::mock('PDO');
        $this->connection5 = Phake::mock('PDO');

        $this->tree = new ReplicationTree($this->connection1);
        $this->tree->addSlave($this->connection1, $this->connection2);
        $this->tree->addSlave($this->connection2, $this->connection3);
        $this->tree->addSlave($this->connection2, $this->connection4);

        $this->manager = Phake::partialMock(__NAMESPACE__ . '\ReplicationManager', $this->tree);

        Phake::when($this->manager)
            ->secondsBehindMaster($this->connection1)
            ->thenReturn(null);

        Phake::when($this->manager)
            ->secondsBehindMaster($this->connection2)
            ->thenReturn(5);

        Phake::when($this->manager)
            ->secondsBehindMaster($this->connection3)
            ->thenReturn(10);

        Phake::when($this->manager)
            ->secondsBehindMaster($this->connection4)
            ->thenReturn(20);

        Phake::when($this->manager)
            ->secondsBehindMaster($this->connection5)
            ->thenReturn(null);
    }

    public function testReplicationTree()
    {
        $this->assertSame($this->tree, $this->manager->replicationTree());
    }
}
