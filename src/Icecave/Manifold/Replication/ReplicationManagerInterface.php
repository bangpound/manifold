<?php
namespace Icecave\Manifold\Replication;

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
     * @param PDO $masterConnection The replication master to check against.
     * @param PDO $slaveConnection  The replication slave.
     *
     * @return integer                           The replication delay between $masterConnection and $slaveConnection, in seconds.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delay(PDO $masterConnection, PDO $slaveConnection);

    /**
     * Check if a slave's replication delay is within the given threshold.
     *
     * @param integer $threshold        The threshold delay (in seconds).
     * @param PDO     $masterConnection The replication master to check against.
     * @param PDO     $slaveConnection  The replication slave.
     *
     * @return boolean                           True if the slave's replication delay is less than or equal to $threshold.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delayWithin(
        $threshold,
        PDO $masterConnection,
        PDO $slaveConnection
    );

    /**
     * Check if a slave is replicating.
     *
     * This function will return false if any of the 'links' in the replication path between $masterConnection and $slaveConnection are not
     * replicating.
     *
     * @param PDO $masterConnection The replication master to check against.
     * @param PDO $slaveConnection  The replication slave.
     *
     * @return boolean                           True if $slaveConnection is replicating; otherwise, false.
     * @throws Exception\NotReplicatingException If $slaveConnection is not a replication slave of $masterConnection.
     */
    public function isReplicating(PDO $masterConnection, PDO $slaveConnection);

    /**
     * Wait for a slave replication to catch up to the current point on the given master.
     *
     * @param PDO          $masterConnection The replication master to check against.
     * @param PDO          $slaveConnection  The replication slave.
     * @param integer|null $timeout          The maximum time to wait in seconds, or null to wait indefinitely.
     *
     * @return boolean                           False if the wait operation times out before completion; otherwise, true.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function wait(
        PDO $masterConnection,
        PDO $slaveConnection,
        $timeout = null
    );
}
