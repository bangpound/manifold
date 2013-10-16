<?php
namespace Icecave\Manifold\Replication;

use Icecave\Collections\Exception\UnknownKeyException;
use Icecave\Collections\Map;
use Icecave\Collections\Set;
use Icecave\Manifold\TypeCheck\TypeCheck;
use InvalidArgumentException;
use PDO;
use stdClass;

/**
 * Represents a tree of replicating databases.
 */
class ReplicationTree implements ReplicationTreeInterface
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
     * @return boolean                              True if the given connection is the root of this tree; otherwise, false.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isRoot(PDO $connection)
    {
        $this->typeCheck->isRoot(func_get_args());

        if (!$this->hasConnection($connection)) {
            throw new Exception\UnknownConnectionException($connection);
        }

        return $connection === $this->replicationRoot;
    }

    /**
     * Check if a connection is a leaf in this tree.
     *
     * @param PDO $connection The connection to check.
     *
     * @return boolean                              True if the given connection is a leaf of this tree; otherwise, false.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
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
     * @return boolean                              True if the given connection is a replication master in this tree; otherwise, false.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
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
     * @return boolean                              True if the given connection is a replication slave in this tree; otherwise, false.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
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
     * @return PDO|null                             The replication master for the given slave, or null if it is the replication root.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
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
     * @return Set<PDO>                             The replication slaves for the given master.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
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
     * @return boolean                              True if $masterConnection is anywhere above $slaveConnection in the replication hierarchy; otherwise, false.
     * @throws Exception\UnknownConnectionException If either connection is not found in this tree.
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
     * @return boolean                              True if $masterConnection is the replication master of $slaveConnection; otherwise, false.
     * @throws Exception\UnknownConnectionException If the master connection is not found in this tree.
     */
    public function isMasterOf(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->isMasterOf(func_get_args());

        if (!$this->hasConnection($masterConnection)) {
            throw new Exception\UnknownConnectionException($masterConnection);
        }

        return $this->masterOf($slaveConnection) === $masterConnection;
    }

    /**
     * Count the number of hops between a master and slave connection.
     *
     * @param PDO $masterConnection The master connection.
     * @param PDO $slaveConnection  The slave connection.
     *
     * @return integer|null                         The number of hops (difference in depth) between $masterConnection and $slaveConnection, or null if $masterConnection is not replicating to $slaveConnection.
     * @throws Exception\UnknownConnectionException If either connection is not found in this tree.
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
     * Compute the replication path between a master and slave connection.
     *
     * The result is an array containing 2-tuples of master and slave for each step in the replication hierarchy.
     *
     * @param PDO $masterConnection The master connection.
     * @param PDO $slaveConnection  The slave connection.
     *
     * @return array<tuple<PDO,PDO>>|null           The replication path between the master and slave connection, or null if $slaveConnection is not replicating from $masterConnection.
     * @throws Exception\UnknownConnectionException If either connection is not found in this tree.
     */
    public function replicationPath(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->replicationPath(func_get_args());

        // Ensure connections are in the tree ...
        $this->getEntry($masterConnection);
        $this->getEntry($slaveConnection);

        if ($masterConnection === $slaveConnection) {
            return array();
        }

        $path = array();

        do {
            if ($masterConnection === $slaveConnection) {
                return array_reverse($path);
            }

            $master = $this->connections[$slaveConnection]->master;
            $path[] = array($master, $slaveConnection);
            $slaveConnection = $master;
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
     *
     * @throws Exception\UnknownConnectionException If the master connection is not found in this tree.
     */
    public function addSlave(PDO $masterConnection, PDO $slaveConnection)
    {
        $this->typeCheck->addSlave(func_get_args());

        $masterEntry = $this->getEntry($masterConnection);
        $this->connections->add(
            $slaveConnection,
            $this->createEntry($masterConnection)
        );
        $masterEntry->slaves->add($slaveConnection);
    }

    /**
     * Remove a slave connection.
     *
     * @param PDO $connection The connection to remove.
     *
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function removeSlave(PDO $connection)
    {
        $this->typeCheck->removeSlave(func_get_args());

        if ($this->isRoot($connection)) {
            throw new InvalidArgumentException(
                'The root connection can not be removed from the tree.'
            );
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
            throw new Exception\UnknownConnectionException($connection, $e);
        }
    }

    private $typeCheck;
    private $replicationRoot;
    private $connections;
}
