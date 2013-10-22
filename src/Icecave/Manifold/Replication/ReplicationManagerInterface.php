<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use PDO;

interface ReplicationManagerInterface
{
    /**
     * Fetch the replication tree upon which this manager operates.
     *
     * @return ReplicationTree The replication tree upon which this manager operates.
     */
    public function tree();

    /**
     * Fetch a replication slave's delay.
     *
     * @param PDO $slaveConnection       The replication slave.
     * @param PDO $masterConnection|null The replication master to check against, or null to use the slave's direct master.
     *
     * @return Duration                          The replication delay between $masterConnection and $slaveConnection.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delay(PDO $slaveConnection, PDO $masterConnection = null);

    /**
     * Check if a slave's replication delay is within the given threshold.
     *
     * @param TimeSpanInterface|integer $threshold             The threshold delay.
     * @param PDO                       $slaveConnection       The replication slave.
     * @param PDO                       $masterConnection|null The replication master to check against, or null to use the slave's direct master.
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
     * This function will return false if any of the 'links' in the replication path between $masterConnection and $slaveConnection are not
     * replicating.
     *
     * @param PDO      $slaveConnection  The replication slave.
     * @param PDO|null $masterConnection The replication master to check against, or null to use the slave's direct master.
     *
     * @return boolean                           True if $slaveConnection is replicating; otherwise, false.
     * @throws Exception\NotReplicatingException If $slaveConnection is not a replication slave of $masterConnection.
     */
    public function isReplicating(
        PDO $slaveConnection,
        PDO $masterConnection = null
    );

    /**
     * Wait for a slave replication to catch up to the current point on the given master.
     *
     * @param PDO                            $slaveConnection       The replication slave.
     * @param TimeSpanInterface|integer|null $timeout               The maximum time to wait, or null to wait indefinitely.
     * @param PDO                            $masterConnection|null The replication master to check against, or null to use the slave's direct master.
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
