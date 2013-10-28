<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimePointInterface;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use PDO;

/**
 * The interface implemented by connection pool member selectors.
 */
interface ConnectionPoolMemberSelectorInterface
{
    /**
     * Get a single connection from a pool.
     *
     * No replication delay threshold is enforced, but replication must be running.
     *
     * @param ConnectionPoolInterface $pool The pool to select from.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(ConnectionPoolInterface $pool);

    /**
     * Get a single connection from a pool by selecting the connection with the
     * least replication delay.
     *
     * @param ConnectionPoolInterface        $pool      The pool to select from.
     * @param TimeSpanInterface|integer|null $threshold The maximum allowable replication delay, or null to allow any amount of delay.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function selectByLeastDelay(
        ConnectionPoolInterface $pool,
        $threshold = null
    );

    /**
     * Get a single connection from a pool by selecting any connection with a
     * replication delay less than the specified maximum.
     *
     * @param ConnectionPoolInterface   $pool      The pool to select from.
     * @param TimeSpanInterface|integer $threshold The maximum allowable replication delay.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function selectByAcceptableDelay(
        ConnectionPoolInterface $pool,
        $threshold
    );

    /**
     * Get a single connection from a pool by selecting any connection that is
     * at least up to date with a given time point.
     *
     * @param ConnectionPoolInterface    $pool      The pool to select from.
     * @param TimePointInterface|integer $timePoint The minimum cut-off time for replication delay.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function selectByTime(ConnectionPoolInterface $pool, $timePoint);
}
