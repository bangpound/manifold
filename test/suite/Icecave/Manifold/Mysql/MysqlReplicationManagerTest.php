<?php
namespace Icecave\Manifold\Mysql;

use Eloquent\Liberator\Liberator;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Manifold\Replication\ReplicationTree;
use PHPUnit_Framework_TestCase;
use Phake;
use stdClass;

class MysqlReplicationManagerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection1 = Phake::mock('PDO');
        $this->connection2 = Phake::mock('PDO');
        $this->tree = new ReplicationTree($this->connection1);
        $this->manager = Liberator::liberate(new MysqlReplicationManager($this->tree));

        $this->statement1 = Phake::mock('PDOStatement');
        $this->statement2 = Phake::mock('PDOStatement');
    }

    public function testAmountBehindMaster()
    {
        $result = new stdClass;
        $result->Seconds_Behind_Master = '111';
        $emptyResult = new stdClass;
        $emptyResult->Seconds_Behind_Master = null;
        Phake::when($this->connection2)->query('SHOW SLAVE STATUS')->thenReturn($this->statement1);
        Phake::when($this->statement1)->fetchObject()->thenReturn($result)->thenReturn($emptyResult)->thenReturn(null);

        $this->assertEquals(
            new Duration(111),
            $this->manager->amountBehindMaster($this->connection1, $this->connection2)
        );
        $this->assertNull($this->manager->amountBehindMaster($this->connection1, $this->connection2));
        $this->assertNull($this->manager->amountBehindMaster($this->connection1, $this->connection2));
    }

    public function testDoWaitNoTimeout()
    {
        $statusResult = new stdClass;
        $statusResult->File = 'foo';
        $statusResult->Position = 'bar';
        Phake::when($this->connection1)->query('SHOW MASTER STATUS')->thenReturn($this->statement1);
        Phake::when($this->statement1)->fetchObject()->thenReturn($statusResult);
        $waitResult = new stdClass;
        $waitResult->events = '1';
        $waitResultTimeout = new stdClass;
        $waitResultTimeout->events = '-1';
        Phake::when($this->connection2)
            ->prepare('SELECT MASTER_POS_WAIT(:file, :position) AS events')
            ->thenReturn($this->statement2);
        Phake::when($this->statement2)->fetchObject()->thenReturn($waitResult)->thenReturn($waitResultTimeout);

        $this->assertTrue($this->manager->doWait($this->connection1, $this->connection2));
        $this->assertFalse($this->manager->doWait($this->connection1, $this->connection2));
        $prepare = Phake::verify($this->connection2, Phake::times(2))
            ->prepare('SELECT MASTER_POS_WAIT(:file, :position) AS events');
        $execute = Phake::verify($this->statement2, Phake::times(2))
            ->execute(array('file' => 'foo', 'position' => 'bar'));
        $fetch = Phake::verify($this->statement2, Phake::times(2))->fetchObject();
        Phake::inOrder($prepare, $execute, $fetch, $prepare, $execute, $fetch);
    }

    public function testDoWaitWithTimeout()
    {
        $statusResult = new stdClass;
        $statusResult->File = 'foo';
        $statusResult->Position = 'bar';
        Phake::when($this->connection1)->query('SHOW MASTER STATUS')->thenReturn($this->statement1);
        Phake::when($this->statement1)->fetchObject()->thenReturn($statusResult);
        $waitResult = new stdClass;
        $waitResult->events = '1';
        $waitResultTimeout = new stdClass;
        $waitResultTimeout->events = '-1';
        Phake::when($this->connection2)
            ->prepare('SELECT MASTER_POS_WAIT(:file, :position, :timeout) AS events')
            ->thenReturn($this->statement2);
        Phake::when($this->statement2)->fetchObject()->thenReturn($waitResult)->thenReturn($waitResultTimeout);

        $this->assertTrue($this->manager->doWait($this->connection1, $this->connection2, new Duration(111)));
        $this->assertFalse($this->manager->doWait($this->connection1, $this->connection2, new Duration(111)));
        $prepare = Phake::verify($this->connection2, Phake::times(2))
            ->prepare('SELECT MASTER_POS_WAIT(:file, :position, :timeout) AS events');
        $execute = Phake::verify($this->statement2, Phake::times(2))
            ->execute(array('file' => 'foo', 'position' => 'bar', 'timeout' => 111));
        $fetch = Phake::verify($this->statement2, Phake::times(2))->fetchObject();
        Phake::inOrder($prepare, $execute, $fetch, $prepare, $execute, $fetch);
    }

    public function testDoWaitFailureNotMaster()
    {
        Phake::when($this->connection1)->query('SHOW MASTER STATUS')->thenReturn($this->statement1);
        Phake::when($this->statement1)->fetchObject()->thenReturn(null);

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NotReplicatingException');
        $this->manager->doWait($this->connection1, $this->connection2);
    }

    public function testDoWaitFailureNotReplicating()
    {
        $statusResult = new stdClass;
        $statusResult->File = 'foo';
        $statusResult->Position = 'bar';
        Phake::when($this->connection1)->query('SHOW MASTER STATUS')->thenReturn($this->statement1);
        Phake::when($this->statement1)->fetchObject()->thenReturn($statusResult);
        $waitResult = new stdClass;
        $waitResult->events = null;
        Phake::when($this->connection2)
            ->prepare('SELECT MASTER_POS_WAIT(:file, :position) AS events')
            ->thenReturn($this->statement2);
        Phake::when($this->statement2)->fetchObject()->thenReturn($waitResult);

        $this->setExpectedException('Icecave\Manifold\Replication\Exception\NotReplicatingException');
        $this->manager->doWait($this->connection1, $this->connection2);
    }
}
