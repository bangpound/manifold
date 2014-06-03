<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by cloneable connections.
 */
interface CloneableConnectionInterface extends ConnectionInterface
{
    /**
     * Create a clone of this connection.
     *
     * @return CloneableConnectionInterface The cloned connection.
     */
    public function cloneConnection();
}
