<?php
namespace Icecave\Manifold\Replication;

use PDO;

interface ReplicationManagerInterface
{
    /**
     * Fetch a replication slave's delay.
     *
     * @param PDO      $slave  The replication slave.
     * @param PDO|null $master The replication master to check against, or null to use the replication root.
     *
     * @return integer                           The replication delay between $master and $slave, in seconds.
     * @throws Exception\NotReplicatingException If $slave is not replicating from $master.
     */
    public function replicationDelay(PDO $slave, PDO $master = null);

    /**
     * Check if a slave is replicating.
     *
     * @param PDO      $slave  The replication slave to check.
     * @param PDO|null $master The master to check against.
     *
     * @return boolean True if $slave is replicating (from $master if provided); otherwise, false.
     */
    public function isReplicating(PDO $slave, PDO $master = null);

    /**
     * Wait for a slave replication to catch up to the current point on the given master.
     *
     * @param PDO          $slave   The replication slave on which to wait.
     * @param PDO|null     $master  The replication master to check against, or null to use the replication root.
     * @param integer|null $timeout The maximum time to wait in seconds, or null to wait indefinitely.
     *
     * @return boolean False if the wait operation times out before complection; otherwise, true.
     */
    public function waitForReplication(PDO $slave, PDO $master = null, $timeout = null);
}
