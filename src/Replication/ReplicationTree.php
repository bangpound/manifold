<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\ConnectionInterface;
use InvalidArgumentException;
use stdClass;

/**
 * Represents a tree of replicating databases.
 */
class ReplicationTree implements ReplicationTreeInterface
{
    /**
     * @param ConnectionInterface $replicationRoot The root database of the replication hierarchy.
     */
    public function __construct(ConnectionInterface $replicationRoot)
    {
        $this->replicationRoot = $replicationRoot;
        $this->connections = array();
        $this->connections[$replicationRoot->name()] = $this->createEntry();
    }

    /**
     * Fetch the root database of the replication hierarchy.
     *
     * @return ConnectionInterface The root database of the replication hierarchy.
     */
    public function replicationRoot()
    {
        return $this->replicationRoot;
    }

    /**
     * Check if this tree contains a given connection.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean True if the given connection is present in this tree.
     */
    public function hasConnection(ConnectionInterface $connection)
    {
        return array_key_exists($connection->name(), $this->connections);
    }

    /**
     * Check if a connection is the root of this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is the root of this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isRoot(ConnectionInterface $connection)
    {
        if (!$this->hasConnection($connection)) {
            throw new Exception\UnknownConnectionException($connection);
        }

        return $connection === $this->replicationRoot;
    }

    /**
     * Check if a connection is a leaf in this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is a leaf of this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isLeaf(ConnectionInterface $connection)
    {
        return count($this->getEntry($connection)->slaves) < 1;
    }

    /**
     * Check if a connection is a replication master in this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is a replication master in this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isMaster(ConnectionInterface $connection)
    {
        return !$this->isLeaf($connection);
    }

    /**
     * Check if a connection is a replication slave in this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is a replication slave in this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isSlave(ConnectionInterface $connection)
    {
        return !$this->isRoot($connection);
    }

    /**
     * Get the replication master for a given slave connection.
     *
     * @param ConnectionInterface $connection The slave connection.
     *
     * @return ConnectionInterface|null             The replication master for the given slave, or null if it is the replication root.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function masterOf(ConnectionInterface $connection)
    {
        return $this->getEntry($connection)->master;
    }

    /**
     * Get the replication slaves for a given master connection.
     *
     * @param ConnectionInterface $connection The master connection.
     *
     * @return array<ConnectionInterface>           The replication slaves for the given master.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function slavesOf(ConnectionInterface $connection)
    {
        return $this->getEntry($connection)->slaves;
    }

    /**
     * Check if a given master is replicating to a given slave.
     *
     * @param ConnectionInterface      $slaveConnection  The slave connection.
     * @param ConnectionInterface|null $masterConnection The master connection, or null to use the replication root.
     *
     * @return boolean                              True if $masterConnection is anywhere above $slaveConnection in the replication hierarchy.
     * @throws Exception\UnknownConnectionException If either connection is not found in this tree.
     */
    public function isReplicatingTo(
        ConnectionInterface $slaveConnection,
        ConnectionInterface $masterConnection = null
    ) {
        return $this->countHops($slaveConnection, $masterConnection) > 0;
    }

    /**
     * Check if a given connection is the replication master of another connection.
     *
     * @param ConnectionInterface $masterConnection The master connection.
     * @param ConnectionInterface $slaveConnection  The slave connection.
     *
     * @return boolean                              True if $masterConnection is the replication master of $slaveConnection.
     * @throws Exception\UnknownConnectionException If the master connection is not found in this tree.
     */
    public function isMasterOf(
        ConnectionInterface $masterConnection,
        ConnectionInterface $slaveConnection
    ) {
        if (!$this->hasConnection($masterConnection)) {
            throw new Exception\UnknownConnectionException($masterConnection);
        }

        return $this->masterOf($slaveConnection) === $masterConnection;
    }

    /**
     * Count the number of hops between a master and slave connection.
     *
     * @param ConnectionInterface      $slaveConnection  The slave connection.
     * @param ConnectionInterface|null $masterConnection The master connection, or null to use the replication root.
     *
     * @return integer|null                         The number of hops (difference in depth) between $masterConnection and $slaveConnection, or null if $masterConnection is not replicating to $slaveConnection.
     * @throws Exception\UnknownConnectionException If either connection is not found in this tree.
     */
    public function countHops(
        ConnectionInterface $slaveConnection,
        ConnectionInterface $masterConnection = null
    ) {
        if (null === $masterConnection) {
            $masterConnection = $this->replicationRoot();
        }

        // Ensure connections are in the tree ...
        $this->getEntry($masterConnection);
        $this->getEntry($slaveConnection);

        $hops = 0;

        do {
            if ($masterConnection === $slaveConnection) {
                return $hops;
            }
            $slaveConnection =
                $this->connections[$slaveConnection->name()]->master;
            ++$hops;
        } while ($slaveConnection);

        return null;
    }

    /**
     * Compute the replication path between a master and slave connection.
     *
     * The result is an array containing 2-tuples of master and slave for each step in the replication hierarchy.
     *
     * @param ConnectionInterface      $slaveConnection  The slave connection.
     * @param ConnectionInterface|null $masterConnection The master connection, or null to use the replication root.
     *
     * @return array<tuple<ConnectionInterface,ConnectionInterface>>|null The replication path between the master and slave connection, or null if $slaveConnection is not replicating from $masterConnection.
     * @throws Exception\UnknownConnectionException                       If either connection is not found in this tree.
     */
    public function replicationPath(
        ConnectionInterface $slaveConnection,
        ConnectionInterface $masterConnection = null
    ) {
        if (null === $masterConnection) {
            $masterConnection = $this->replicationRoot();
        }

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

            $master = $this->connections[$slaveConnection->name()]->master;
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
     * @param ConnectionInterface $masterConnection The master connection.
     * @param ConnectionInterface $slaveConnection  The slave connection to add.
     *
     * @throws Exception\UnknownConnectionException If the master connection is not found in this tree.
     */
    public function addSlave(
        ConnectionInterface $masterConnection,
        ConnectionInterface $slaveConnection
    ) {
        $masterEntry = $this->getEntry($masterConnection);
        $this->connections[$slaveConnection->name()] =
            $this->createEntry($masterConnection);
        $masterEntry->slaves[] = $slaveConnection;
    }

    /**
     * Remove a slave connection.
     *
     * @param ConnectionInterface $connection The connection to remove.
     *
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function removeSlave(ConnectionInterface $connection)
    {
        if ($this->isRoot($connection)) {
            throw new InvalidArgumentException(
                'The root connection can not be removed from the tree.'
            );
        }

        foreach ($this->slavesOf($connection) as $slave) {
            $this->removeSlave($slave);
        }

        $masterEntry = $this->getEntry($this->getEntry($connection)->master);
        $slaveIndex = array_search($connection, $masterEntry->slaves, true);
        if (false !== $slaveIndex) {
            unset($masterEntry->slaves[$slaveIndex]);
        }
        unset($this->connections[$connection->name()]);
    }

    /**
     * @param ConnectionInterface|null $masterConnection
     *
     * @return stdClass
     */
    private function createEntry(ConnectionInterface $masterConnection = null)
    {
        $entry = new stdClass();
        $entry->master = $masterConnection;
        $entry->slaves = array();

        return $entry;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return stdClass
     */
    private function getEntry(ConnectionInterface $connection)
    {
        if ($this->hasConnection($connection)) {
            return $this->connections[$connection->name()];
        }

        throw new Exception\UnknownConnectionException($connection);
    }

    private $replicationRoot;
    private $connections;
}
