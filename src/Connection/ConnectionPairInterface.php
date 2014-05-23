<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by connection read/write pairs.
 */
interface ConnectionPairInterface
{
    /**
     * Get the write connection.
     *
     * @return ConnectionInterface The write connection.
     */
    public function write();

    /**
     * Get the read connection.
     *
     * @return ConnectionInterface The read connection.
     */
    public function read();
}
