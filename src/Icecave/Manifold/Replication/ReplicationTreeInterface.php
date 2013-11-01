<?php
namespace Icecave\Manifold\Replication;

use Icecave\Collections\Set;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The interface implemented by replication trees.
 */
interface ReplicationTreeInterface
{
    /**
     * Fetch the root database of the replication hierarchy.
     *
     * @return ConnectionInterface The root database of the replication hierarchy.
     */
    public function replicationRoot();

    /**
     * Check if this tree contains a given connection.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean True if the given connection is present in this tree.
     */
    public function hasConnection(ConnectionInterface $connection);

    /**
     * Check if a connection is the root of this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is the root of this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isRoot(ConnectionInterface $connection);

    /**
     * Check if a connection is a leaf in this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is a leaf of this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isLeaf(ConnectionInterface $connection);

    /**
     * Check if a connection is a replication master in this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is a replication master in this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isMaster(ConnectionInterface $connection);

    /**
     * Check if a connection is a replication slave in this tree.
     *
     * @param ConnectionInterface $connection The connection to check.
     *
     * @return boolean                              True if the given connection is a replication slave in this tree.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function isSlave(ConnectionInterface $connection);

    /**
     * Get the replication master for a given slave connection.
     *
     * @param ConnectionInterface $connection The slave connection.
     *
     * @return ConnectionInterface|null             The replication master for the given slave, or null if it is the replication root.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function masterOf(ConnectionInterface $connection);

    /**
     * Get the replication slaves for a given master connection.
     *
     * @param ConnectionInterface $connection The master connection.
     *
     * @return Set<ConnectionInterface>             The replication slaves for the given master.
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function slavesOf(ConnectionInterface $connection);

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
    );

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
    );

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
    );

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
    );

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
    );

    /**
     * Remove a slave connection.
     *
     * @param ConnectionInterface $connection The connection to remove.
     *
     * @throws Exception\UnknownConnectionException If the connection is not found in this tree.
     */
    public function removeSlave(ConnectionInterface $connection);
}
