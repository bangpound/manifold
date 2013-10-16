<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\TypeCheck\TypeCheck;
use PDO;

class ReplicationManager implements ReplicationManagerInterface
{
    /**
     * @param ReplicationTree $replicationTree The replication tree upon which this manager operates.
     */
    public function __construct(ReplicationTree $replicationTree)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->replicationTree = $replicationTree;
    }

    /**
     * Fetch the replication tree upon which this manager operates.
     *
     * @return ReplicationTree The replication tree upon which this manager operates.
     */
    public function replicationTree()
    {
        $this->typeCheck->replicationTree(func_get_args());

        return $this->replicationTree;
    }

    /**
     * Fetch a replication slave's delay.
     *
     * @param PDO $masterConnection The replication master to check against.
     * @param PDO $slaveConnection  The replication slave.
     *
     * @return integer                           The replication delay between $masterConnection and $slaveConnection, in seconds.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function replicationDelay(
        PDO $masterConnection,
        PDO $slaveConnection
    ) {
        $this->typeCheck->replicationDelay(func_get_args());

        $path = $this->replicationTree()->replicationPath(
            $masterConnection,
            $slaveConnection
        );

        if (null === $path) {
            throw new Exception\NotReplicatingException;
        }

        $totalDelay = 0;

        foreach ($path as $element) {
            list(, $slaveConnection) = $element;
            $delay = $this->secondsBehindMaster($slaveConnection);

            if (null === $delay) {
                throw new Exception\NotReplicatingException;
            }

            $totalDelay += $delay;
        }

        return $totalDelay;
    }

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
    public function replicationDelayWithin(
        $threshold,
        PDO $masterConnection,
        PDO $slaveConnection
    ) {
        $this->typeCheck->replicationDelayWithin(func_get_args());

        $path = $this->replicationTree()->replicationPath(
            $masterConnection,
            $slaveConnection
        );

        if (null === $path) {
            throw new Exception\NotReplicatingException;
        }

        for ($index = count($path) - 1; $index >= 0; --$index) {
            list(, $slaveConnection) = $path[$index];
            $delay = $this->secondsBehindMaster($s);

            if (null === $delay) {
                throw new Exception\NotReplicatingException;
            }

            $threshold -= $delay;

            if ($threshold < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a slave is replicating.
     *
     * This function will return false if any of the 'links' in the replication path between $masterConnection and $slaveConnection are not
     * replicating.
     *
     * @param PDO $masterConnection The replication master to check against.
     * @param PDO $slaveConnection  The replication slave.
     *
     * @return boolean True if $slaveConnection is replicating; otherwise, false.
     */
    public function isReplicating(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->isReplicating(func_get_args());

        $path = $this->replicationTree()->replicationPath(
            $masterConnection,
            $slaveConnection
        );

        if (null === $path) {
            return false;
        }

        foreach ($path as $element) {
            list(, $slaveConnection) = $element;
            if (null === $this->secondsBehindMaster($slaveConnection)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Wait for a slave replication to catch up to the current point on the given master.
     *
     * @param PDO          $masterConnection The replication master to check against.
     * @param PDO          $slaveConnection  The replication slave.
     * @param integer|null $timeout          The maximum time to wait in seconds, or null to wait indefinitely.
     *
     * @return boolean                           False if the wait operation times out before complection; otherwise, true.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function waitForReplication(
        PDO $masterConnection,
        PDO $slaveConnection,
        $timeout = null
    ) {
        $this->typeCheck->waitForReplication(func_get_args());
    }

    /**
     * @param PDO $connection
     *
     * @return integer|null
     */
    protected function secondsBehindMaster(PDO $connection)
    {
        $this->typeCheck->secondsBehindMaster(func_get_args());

        $statement = $connection->query('SHOW SLAVE STATUS');
        $result = $statement->fetchObject();

        if (null === $result) {
            return null;
        } elseif (null === $result->Seconds_Behind_Master) {
            return null;
        }

        return intval($result->Seconds_Behind_Master);
    }

    private $typeCheck;
}
