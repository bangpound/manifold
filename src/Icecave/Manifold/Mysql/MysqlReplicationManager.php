<?php
namespace Icecave\Manifold\Mysql;

use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Replication\AbstractReplicationManager;
use Icecave\Manifold\Replication\Exception\NotReplicatingException;
use PDO;
use stdClass;

/**
 * A replication manager for handling multi-tiered MySQL replication
 * hierarchies.
 */
class MysqlReplicationManager extends AbstractReplicationManager
{
    /**
     * Determine how far the supplied connection is behind its master.
     *
     * This method is not expected to handle multiple links in the replication
     * path between $masterConnection and $slaveConnection. Implementations can
     * safely assume that $masterConnection is the direct master of
     * $slaveConnection.
     *
     * @param PDO $masterConnection The replication master to check against.
     * @param PDO $slaveConnection  The replication slave.
     *
     * @return Duration|null The amount of time behind master, or null if $slaveConnection is not replicating from $masterConnection.
     */
    protected function amountBehindMaster(
        PDO $masterConnection,
        PDO $slaveConnection
    ) {
        $result = $this->slaveStatus($slaveConnection);
        if (null === $result || null === $result->Seconds_Behind_Master) {
            return null;
        }

        return new Duration(intval($result->Seconds_Behind_Master));
    }

    /**
     * Wait for a slave replication to catch up to the current point on the
     * given master.
     *
     * This method is not expected to handle multiple links in the replication
     * path between $masterConnection and $slaveConnection. Implementations can
     * safely assume that $masterConnection is the direct master of
     * $slaveConnection.
     *
     * @param PDO                    $masterConnection The replication master to check against.
     * @param PDO                    $slaveConnection  The replication slave.
     * @param TimeSpanInterface|null $timeout          The maximum time to wait, or null to wait indefinitely.
     *
     * @return boolean                 False if the wait operation times out before completion; otherwise, true.
     * @throws NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    protected function doWait(
        PDO $masterConnection,
        PDO $slaveConnection,
        TimeSpanInterface $timeout = null
    ) {
        $timeout = $this->normalizeDuration($timeout);

        $masterStatus = $this->masterStatus($masterConnection);
        if (null === $masterStatus) {
            throw new NotReplicatingException($masterConnection);
        }

        if (null === $timeout) {
            $statement = $slaveConnection->prepare(
                'SELECT MASTER_POS_WAIT(:file, :position) AS events'
            );
            $statement->execute(
                array(
                    'file' => $masterStatus->File,
                    'position' => $masterStatus->Position,
                )
            );
        } else {
            $statement = $slaveConnection->prepare(
                'SELECT MASTER_POS_WAIT(:file, :position, :timeout) AS events'
            );
            $statement->execute(
                array(
                    'file' => $masterStatus->File,
                    'position' => $masterStatus->Position,
                    'timeout' => $timeout->totalSeconds(),
                )
            );
        }

        $result = $statement->fetchObject();
        if (null === $result || null === $result->events) {
            throw new NotReplicatingException($slaveConnection);
        }

        return -1 !== intval($result->events);
    }

    /**
     * Get the slave status of a connection.
     *
     * @param PDO $connection The slave connection.
     *
     * @return stdClass|null The result row, or null if the connection is not a replication slave.
     */
    protected function slaveStatus(PDO $connection)
    {
        return $connection->query('SHOW SLAVE STATUS')->fetchObject();
    }

    /**
     * Get the master status of a connection.
     *
     * @param PDO $connection The master connection.
     *
     * @return stdClass|null The result row, or null if the connection is not a replication master.
     */
    protected function masterStatus(PDO $connection)
    {
        return $connection->query('SHOW MASTER STATUS')->fetchObject();;
    }
}
