<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimePointInterface;
use PDO;

/**
 * Represents a pool of connections with a common replication master.
 *
 * All connections in a replication pool may be used interchangably.
 */
interface ReplicationPoolInterface
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

    /**
     * Get the database connection from the pool with the lowest replication delay.
     *
     * @param integer|null $maximumDelay The maximum replication delay allowed in seconds, or null to allow any.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquire($maximumDelay = null);

    /**
     * Get a single database connection from the pool.
     *
     * No replication delay threshold is enforced, but replication must be running.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireAny();

    /**
     * Get a single database connection from the pool with a replication delay no greater than the given threshold.
     *
     * @param integer $maximumDelay The maximum replication delay in seconds.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireWithMaximumDelay($maximumDelay);

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
