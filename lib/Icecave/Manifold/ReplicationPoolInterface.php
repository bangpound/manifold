<?php
namespace Icecave\Manifold;

use Icecave\Chrono\TimePointInterface;
use Icecave\Chrono\TimeSpan\Duration;
use PDO;

/**
 * Represents a pool of connections with a common replication master.
 *
 * All connections in a replication pool may be used interchangably.
 */
interface ReplicationPoolInterface
{
    /**
     * The connection that is the replication master for all connections in the pool.
     *
     * @return PDO
     */
    public function replicationMaster();

    /**
     * @return array<PDO>
     */
    public function connections();

    /**
     * Get the database connection from the pool with the lowest replication delay.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquire();

    /**
     * Get a single database connection from the pool without a specific replication delay requirement.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireAny();

    /**
     * Get a single database connection from the pool with a replication delay no greater than the given threshold.
     *
     * @param Duration $maximumDelay The maximum replication delay.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireWithMaximumDelay(Duration $maximumDelay)

    /**
     * Get a single database connection from the pool with a replication delay no earlier than the given threshold.
     *
     * @param TimePointInterface $timePoint The minimum cut-off time for replication delay.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireForTime(TimePointInterface $timePoint);
}
