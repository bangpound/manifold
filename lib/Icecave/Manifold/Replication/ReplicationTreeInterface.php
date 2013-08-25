<?php
namespace Icecave\Manifold\Replication;

use PDO;

interface ReplicationTreeInterface
{
    /**
     * The connection that is the replication master for all connections in the tree.
     *
     * @return PDO
     */
    public function replicationRoot();

    /**
     * Fetch an array containing all connections im the tree that are replication masters.
     *
     * @return array<PDO>
     */
    public function replicationMasters();

    /**
     * Fetch the database connections that use the replication root as their master, if any.
     *
     * @return array<PDO>
     */
    public function connections();

    /**
     * Fetch the replication pools that use the replication root as their master, if any.
     *
     * @return array<ReplicationPoolInterface>
     */
    public function pools();
}
