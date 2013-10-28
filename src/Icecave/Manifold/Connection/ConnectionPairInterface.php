<?php
namespace Icecave\Manifold\Connection;

use PDO;

/**
 * The interface implemented by connection read/write pairs.
 */
interface ConnectionPairInterface
{
    /**
     * Get the write connection.
     *
     * @return PDO The write connection.
     */
    public function write();

    /**
     * Get the read connection.
     *
     * @return PDO The read connection.
     */
    public function read();
}
