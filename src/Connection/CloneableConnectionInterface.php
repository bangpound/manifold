<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by cloneable connections.
 */
interface CloneableConnectionInterface extends ConnectionInterface
{
    /**
     * Called after cloning.
     */
    public function __clone();
}
