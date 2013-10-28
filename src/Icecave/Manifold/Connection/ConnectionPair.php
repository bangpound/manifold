<?php
namespace Icecave\Manifold\Connection;

use PDO;

/**
 * The interface implemented by connection read/write pairs.
 */
class ConnectionPair implements ConnectionPairInterface
{
    /**
     * Construct a new read/write connection pair.
     *
     * @param PDO $write The write connection.
     * @param PDO $read  The read connection.
     */
    public function __construct(PDO $write, PDO $read)
    {
        $this->write = $write;
        $this->read = $read;
    }

    /**
     * Get the write connection.
     *
     * @return PDO The write connection.
     */
    public function write()
    {
        return $this->write;
    }

    /**
     * Get the read connection.
     *
     * @return PDO The read connection.
     */
    public function read()
    {
        return $this->read;
    }

    private $write;
    private $read;
}
