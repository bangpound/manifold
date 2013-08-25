<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\Clock\SystemClock;
use Icecave\Chrono\TimePointInterface;
use Icecave\Manifold\Connection;

/**
 * Represents a pool of connections with a common replication master.
 *
 * All connections in a replication pool may be used interchangeably.
 */
class ReplicationPool implements ReplicationPoolInterface
{
    /**
     * @param Connection        $replicationMaster The connection that is the replication master for all connections in the pool.
     * @param array<Connection> $connections       An array containing the connections in the pool, in order of preference.
     */
    public function __construct(Connection $replicationMaster, array $connections)
    {
        $this->replicationMaster = $replicationMaster;
        $this->connections = $connections;
    }

    /**
     * Get the connection that is the replication master for all connections in the pool.
     *
     * @return Connection The connection that is the replication master for all connections in the pool.
     */
    public function replicationMaster()
    {
        return $this->replicationMaster;
    }

    /**
     * Get the connections in the pool.
     *
     * @return array<Connection> An array containing the connections in the pool.
     */
    public function connections()
    {
        return $this->connections;
    }

    private $replicationMaster;
    private $connections;
}
