<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use PDO;

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
     * An optional threshold can be specified. This will prevent the manager
     * from continuing to add up replication delay from further up the path if
     * the threshold has already been surpassed. In this case, null will be
     * returned instead of a duration. This allows for improved performance if
     * the delay must be below a threshold to be useful.
     *
     * @param PDO                            $slaveConnection       The replication slave.
     * @param TimeSpanInterface|integer|null $threshold             The maximum allowable replication delay, or null to allow any amount of delay.
     * @param PDO                            $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return Duration|null                     The replication delay between $masterConnection and $slaveConnection, or null if a threshold is passed, and the duration surpasses that threshold.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delay(
        PDO $slaveConnection,
        $threshold = null,
        PDO $masterConnection = null
    );

    /**
     * Check if a slave's replication delay is within the given threshold.
     *
     * This method traverses up the replication path, adding up the replication
     * delay at each link to get a total amount. Traversal stops immediately if
     * the delay surpasses the threshold.
     *
     * @param TimeSpanInterface|integer $threshold             The threshold delay.
     * @param PDO                       $slaveConnection       The replication slave.
     * @param PDO                       $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           True if the slave's replication delay is less than or equal to $threshold.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delayWithin(
        $threshold,
        PDO $slaveConnection,
        PDO $masterConnection = null
    );

    /**
     * Check if a slave is replicating.
     *
     * This function will return false if any of the links in the replication
     * path between $masterConnection and $slaveConnection are not replicating.
     *
     * @param PDO      $slaveConnection  The replication slave.
     * @param PDO|null $masterConnection The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           True if $slaveConnection is replicating; otherwise, false.
     * @throws Exception\NotReplicatingException If $slaveConnection is not a replication slave of $masterConnection.
     */
    public function isReplicating(
        PDO $slaveConnection,
        PDO $masterConnection = null
    );

    /**
     * Wait for a slave replication to catch up to the current point on the
     * given master.
     *
     * This method traverses up the replication path, waiting for each link to
     * catch up to its master. If a timeout is specified, this method will
     * return false once the total time spent waiting exceeds this timeout.
     *
     * @param PDO                            $slaveConnection       The replication slave.
     * @param TimeSpanInterface|integer|null $timeout               The maximum time to wait, or null to wait indefinitely.
     * @param PDO                            $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           False if the wait operation times out before completion; otherwise, true.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function wait(
        PDO $slaveConnection,
        $timeout = null,
        PDO $masterConnection = null
    );
}
