<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimePointInterface;
use PDO;

/**
 * Represents a pool of connections with a common replication master.
 *
 * All connections in a replication pool may be used interchangably.
 */
interface PoolInterface
{
    /**
     * Get the connection that is the replication master for all connections in the pool.
     *
     * @return PDO The connection that is the replication master for all connections in the pool.
     */
    public function replicationMaster();

    /**
     * Get the connections in the pool.
     *
     * @return array<PDO> An array containing the connections in the pool.
     */
    public function connections();
}
