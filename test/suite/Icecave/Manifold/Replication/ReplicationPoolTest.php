<?php
// namespace Icecave\Manifold\Replication;

// use Eloquent\Liberator\Liberator;
// use PDO;
// use Phake;
// use PHPUnit_Framework_TestCase;

// class ReplicationPoolTest extends PHPUnit_Framework_TestCase
// {
//     public function setUp()
//     {
//         $this->replicationMaster = Phake::mock('PDO');

//         $this->connection1 = Phake::mock('PDO');
//         $this->connection2 = Phake::mock('PDO');
//         $this->connection3 = Phake::mock('PDO');

//         $this->connections = array($this->connection1, $this->connection2, $this->connection3);

//         $this->pool = Phake::partialMock(
//             __NAMESPACE__ . '\ReplicationPool',
//             $this->replicationMaster,
//             $this->connections
//         );
//     }

//     public function testReplicationMaster()
//     {
//         $this->assertSame($this->replicationMaster, $this->pool->replicationMaster());
//     }

//     public function testConnections()
//     {
//         $this->assertSame($this->connections, $this->pool->connections());
//     }

//     public function testAcquire()
//     {

//     }

//     public function testAcquireAny()
//     {

//     }

//     public function testAcquireWithMaximumDelay()
//     {

//     }

//     public function testAcquireForTime()
//     {

//     }

//     public function testOrderedConnections()
//     {
//         Phake::when($this->pool)
//             ->isConnected
//     }

//     public function testIsConnected()
//     {

//     }

//     public function testReplicationDelay()
//     {

//     }
// }
