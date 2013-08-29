<?php
namespace Icecave\Manifold\Replication;

use Icecave\Collections\Exception\UnknownKeyException;
use Icecave\Collections\Map;
use Icecave\Collections\Set;
use Icecave\Manifold\Exception\UnknownDatabaseException;
use Icecave\Manifold\TypeCheck\TypeCheck;
use PDO;
use stdClass;
use InvalidArgumentException;

/**
 * Represents a tree of replicating databases.
 */
class ReplicationTree
{
    /**
     * @param PDO $replicationRoot The root database of the replication hierarchy.
     */
    public function __construct(PDO $replicationRoot)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->replicationRoot = $replicationRoot;
        $this->connections = new Map;
        $this->connections[$replicationRoot] = $this->createEntry();
    }

    /**
     * Fetch the root database of the replication hierarchy.
     *
     * @return PDO The root database of the replication hierarchy.
     */
    public function replicationRoot()
    {
        $this->typeCheck->replicationRoot(func_get_args());

        return $this->replicationRoot;
    }

    /**
     * Check if this tree contains a given connection.
     *
     * @param PDO $connection The connection to check.
     *
     * @return boolean True if the given connection is present in this tree; otherwise, false.
     */
    public function hasConnection(PDO $connection)
    {
        $this->typeCheck->hasConnection(func_get_args());

        return $this->connections->hasKey($connection);
    }

    /**
     * Check if a connection is the root of this tree.
     *
     * @param PDO $connection The connection to check.
     *
     * @return boolean True if the given connection is the root of this tree; otherwise, false.
     */
    public function isRoot(PDO $connection)
    {
        $this->typeCheck->isRoot(func_get_args());

        if (!$this->hasConnection($connection)) {
            throw new UnknownDatabaseException;
        }

        return $connection === $this->replicationRoot;
    }

    /**
     * Check if a connection is a leaf in this tree.
     *
     * @param PDO $connection The connection to check.
     *
     * @return boolean True if the given connection is a leaf of this tree; otherwise, false.
     */
    public function isLeaf(PDO $connection)
    {
        $this->typeCheck->isLeaf(func_get_args());

        return $this->getEntry($connection)->slaves->isEmpty();
    }

    /**
     * Check if a connection is a replication master in this tree.
     *
     * @param PDO $connection The connection to check.
     *
     * @return boolean True if the given connection is a replication master in this tree; otherwise, false.
     */
    public function isMaster(PDO $connection)
    {
        $this->typeCheck->isMaster(func_get_args());

        return !$this->isLeaf($connection);
    }

    /**
     * Check if a connection is a replication slave in this tree.
     *
     * @param PDO $connection The connection to check.
     *
     * @return boolean True if the given connection is a replication slave in this tree; otherwise, false.
     */
    public function isSlave(PDO $connection)
    {
        $this->typeCheck->isSlave(func_get_args());

        return !$this->isRoot($connection);
    }

    /**
     * Get the replication master for a given slave connection.
     *
     * @param PDO $connection The slave connection.
     *
     * @return PDO|null The replication master for the given slave, or null if it is the replication root.
     */
    public function masterOf(PDO $connection)
    {
        $this->typeCheck->masterOf(func_get_args());

        return $this->getEntry($connection)->master;
    }

    /**
     * Get the replication slaves for a given master connection.
     *
     * @param PDO $connection The master connection.
     *
     * @return Set<PDO> The replication slaves for the given master.
     */
    public function slavesOf(PDO $connection)
    {
        $this->typeCheck->slavesOf(func_get_args());

        return clone $this->getEntry($connection)->slaves;
    }

    /**
     * Check if a given master is replicating to a given slave.
     *
     * @param PDO $masterConnection The master connection.
     * @param PDO $slaveConnection  The slave connection.
     *
     * @return boolean True if $masterConnection is anywhere above $slaveConnection in the replication hierarchy; otherwise, false.
     */
    public function isReplicatingTo(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->isReplicatingTo(func_get_args());

        return $this->countHops($masterConnection, $slaveConnection) > 0;
    }

    /**
     * Check if a given connection is the replication master of another connection.
     *
     * @param PDO $masterConnection The master connection.
     * @param PDO $slaveConnection  The slave connection.
     *
     * @return boolean True if $masterConnection is the replication master of $slaveConnection; otherwise, false.
     */
    public function isMasterOf(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->isMasterOf(func_get_args());

        if (!$this->hasConnection($masterConnection)) {
            throw new UnknownDatabaseException;
        }

        return $this->masterOf($slaveConnection) === $masterConnection;
    }

    /**
     * Count the number of hops between a master and slave connection.
     *
     * @param PDO $masterConnection The master connection.
     * @param PDO $slaveConnection  The slave connection.
     *
     * @return integer|null The number of hops (difference in depth) between $masterConnection and $slaveConnection, or null if $masterConnection is not replicating to $slaveConnection.
     */
    public function countHops(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->countHops(func_get_args());

        // Ensure connections are in the tree ...
        $this->getEntry($masterConnection);
        $this->getEntry($slaveConnection);

        $hops = 0;

        do {
            if ($masterConnection === $slaveConnection) {
                return $hops;
            }
            $slaveConnection = $this->connections[$slaveConnection]->master;
            ++$hops;
        } while ($slaveConnection);

        return null;
    }

    /**
     * Add a slave connection.
     *
     * The given master must have already been added to the tree.
     *
     * @param PDO $masterConnection The master connection.
     * @param PDO $slaveConnection  The slave connection to add.
     */
    public function addSlave(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->addSlave(func_get_args());

        $masterEntry = $this->getEntry($masterConnection);
        $this->connections->add($slaveConnection, $this->createEntry($masterConnection));
        $masterEntry->slaves->add($slaveConnection);
    }

    /**
     * Remove a slave connection.
     *
     * @param PDO $connection The connection to remove.
     */
    public function removeSlave(PDO $connection)
    {
        $this->typeCheck->removeSlave(func_get_args());

        if ($this->isRoot($connection)) {
            throw new InvalidArgumentException('The root connection can not be removed from the tree.');
        }

        foreach ($this->slavesOf($connection) as $slave) {
            $this->removeSlave($slave);
        }

        $masterEntry = $this->getEntry($this->getEntry($connection)->master);
        $masterEntry->slaves->remove($connection);
        $this->connections->remove($connection);
    }

    /**
     * @param PDO|null $masterConnection
     *
     * @return stdClass
     */
    private function createEntry(PDO $masterConnection = null)
    {
        $this->typeCheck->createEntry(func_get_args());

        $entry = new stdClass;
        $entry->master = $masterConnection;
        $entry->slaves = new Set;

        return $entry;
    }

    /**
     * @param PDO $connection
     *
     * @return stdClass
     */
    private function getEntry(PDO $connection)
    {
        $this->typeCheck->getEntry(func_get_args());

        try {
            return $this->connections[$connection];
        } catch (UnknownKeyException $e) {
            throw new UnknownDatabaseException($e);
        }
    }

    private $typeCheck;
    private $replicationRoot;
    private $connections;
}
