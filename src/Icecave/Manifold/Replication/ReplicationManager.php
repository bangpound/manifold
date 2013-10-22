<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use PDO;

class ReplicationManager implements ReplicationManagerInterface
{
    /**
     * @param ReplicationTree $tree The replication tree upon which this manager operates.
     */
    public function __construct(ReplicationTree $tree)
    {
        $this->tree = $tree;
    }

    /**
     * Fetch the replication tree upon which this manager operates.
     *
     * @return ReplicationTree The replication tree upon which this manager operates.
     */
    public function tree()
    {
        return $this->tree;
    }

    /**
     * Fetch a replication slave's delay.
     *
     * @param PDO $slaveConnection       The replication slave.
     * @param PDO $masterConnection|null The replication master to check against, or null to use the slave's direct master.
     *
     * @return Duration                          The replication delay between $masterConnection and $slaveConnection.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delay(PDO $slaveConnection, PDO $masterConnection = null)
    {
        // if (null === $masterConnection) {
        //     $masterConnection = $this->tree()->masterOf($slaveConnection);
        // }

        // $path = $this->tree()->replicationPath(
        //     $masterConnection,
        //     $slaveConnection
        // );

        // if (null === $path) {
        //     throw new Exception\NotReplicatingException;
        // }

        // $totalDelay = 0;

        // foreach ($path as $element) {
        //     list(, $slaveConnection) = $element;
        //     $delay = $this->secondsBehindMaster($slaveConnection);

        //     if (null === $delay) {
        //         throw new Exception\NotReplicatingException;
        //     }

        //     $totalDelay += $delay;
        // }

        // return $totalDelay;
    }

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
    ) {
        // if (null === $masterConnection) {
        //     $masterConnection = $this->tree()->masterOf($slaveConnection);
        // }

        // $path = $this->tree()->replicationPath(
        //     $masterConnection,
        //     $slaveConnection
        // );

        // if (null === $path) {
        //     throw new Exception\NotReplicatingException;
        // }

        // for ($index = count($path) - 1; $index >= 0; --$index) {
        //     list(, $slaveConnection) = $path[$index];
        //     $delay = $this->secondsBehindMaster($s);

        //     if (null === $delay) {
        //         throw new Exception\NotReplicatingException;
        //     }

        //     $threshold -= $delay;

        //     if ($threshold < 0) {
        //         return false;
        //     }
        // }

        // return true;
    }

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
    ) {
        // if (null === $masterConnection) {
        //     $masterConnection = $this->tree()->masterOf($slaveConnection);
        // }

        // $path = $this->tree()->replicationPath(
        //     $masterConnection,
        //     $slaveConnection
        // );

        // if (null === $path) {
        //     return false;
        // }

        // foreach ($path as $element) {
        //     list(, $slaveConnection) = $element;
        //     if (null === $this->secondsBehindMaster($slaveConnection)) {
        //         return false;
        //     }
        // }

        // return true;
    }

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
    ) {
        // if (null === $masterConnection) {
        //     $masterConnection = $this->tree()->masterOf($slaveConnection);
        // }
    }

    /**
     * Determine how far the supplied connection is behind its master.
     *
     * @param PDO $connection The slave connection.
     *
     * @return Duration|null The amount of time behind master.
     */
    protected function amountBehindMaster(PDO $connection)
    {
        // $statement = $connection->query('SHOW SLAVE STATUS');
        // $result = $statement->fetchObject();

        // if (null === $result) {
        //     return null;
        // } elseif (null === $result->Seconds_Behind_Master) {
        //     return null;
        // }

        // return intval($result->Seconds_Behind_Master);
    }

    private $tree;
}
