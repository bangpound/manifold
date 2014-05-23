<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The interface implemented by replication managers.
 */
interface ReplicationManagerInterface
{
    /**
     * Fetch the replication tree upon which this manager operates.
     *
     * @return ReplicationTreeInterface The replication tree upon which this manager operates.
     */
    public function tree();

    /**
     * Fetch a replication slave's delay.
     *
     * This method traverses up the replication path, adding up the replication
     * delay at each link to get a total amount.
     *
     * An optional threshold can be specified. This does not guarantee that the
     * return value will be non-null. Rather, it is used to prevent the manager
     * from continuing to add up replication delay from further up the path if
     * the threshold has already been surpassed. In this case, the returned
     * duration is only to be considered an 'at least' value. The real
     * replication delay could be higher. This allows for improved performance
     * because less connections are potentially made.
     *
     * @param ConnectionInterface            $slaveConnection       The replication slave.
     * @param TimeSpanInterface|integer|null $threshold             The maximum checked replication delay, or null to allow any amount of delay.
     * @param ConnectionInterface            $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return Duration|null                     The replication delay between $masterConnection and $slaveConnection, or null if replication is not running.
     * @throws Exception\NotReplicatingException If there is no replication path from $slaveConnection to $masterConnection.
     */
    public function delay(
        ConnectionInterface $slaveConnection,
        $threshold = null,
        ConnectionInterface $masterConnection = null
    );

    /**
     * Check if a slave is replicating.
     *
     * This function will return false if any of the links in the replication
     * path between $masterConnection and $slaveConnection are not replicating.
     *
     * @param ConnectionInterface      $slaveConnection  The replication slave.
     * @param ConnectionInterface|null $masterConnection The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           True if $slaveConnection is replicating.
     * @throws Exception\NotReplicatingException If $slaveConnection is not a replication slave of $masterConnection.
     */
    public function isReplicating(
        ConnectionInterface $slaveConnection,
        ConnectionInterface $masterConnection = null
    );

    /**
     * Wait for a slave replication to catch up to the current point on the
     * given master.
     *
     * This method traverses up the replication path, waiting for each link to
     * catch up to its master. If a timeout is specified, this method will
     * return false once the total time spent waiting exceeds this timeout.
     *
     * @param ConnectionInterface            $slaveConnection       The replication slave.
     * @param TimeSpanInterface|integer|null $timeout               The maximum time to wait, or null to wait indefinitely.
     * @param ConnectionInterface            $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           False if the wait operation times out before completion; otherwise, true.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function wait(
        ConnectionInterface $slaveConnection,
        $timeout = null,
        ConnectionInterface $masterConnection = null
    );
}
