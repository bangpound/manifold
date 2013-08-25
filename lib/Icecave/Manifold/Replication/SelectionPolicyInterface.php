<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimePointInterface;

/**
 * A policy for choosing the most appropriate database connection to use from connection pool.
 */
interface SelectionPolicyInterface
{
    /**
     * Get the database connection from the pool with the lowest replication delay.
     *
     * @param PoolInterface The connection pool from which the connection is chosen.
     * @param integer|null $maximumDelay The maximum replication delay allowed in seconds, or null to allow any.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquire(PoolInterface $pool, $maximumDelay = null);

    /**
     * Get a single database connection from the pool.
     *
     * No replication delay threshold is enforced, but replication must be running.
     *
     * @param PoolInterface The connection pool from which the connection is chosen.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireAny(PoolInterface $pool);

    /**
     * Get a single database connection from the pool with a replication delay no greater than the given threshold.
     *
     * @param PoolInterface The connection pool from which the connection is chosen.
     * @param integer $maximumDelay The maximum replication delay in seconds.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireWithMaximumDelay(PoolInterface $pool, $maximumDelay);

    /**
     * Get a single database connection from the pool with a replication delay no earlier than the given threshold.
     *
     * @param PoolInterface The connection pool from which the connection is chosen.
     * @param TimePointInterface $timePoint The minimum cut-off time for replication delay.
     *
     * @return PDO
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireForTime(PoolInterface $pool, TimePointInterface $timePoint);
}
