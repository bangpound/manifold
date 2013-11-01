<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by connection read/write pairs.
 */
class ConnectionPair implements ConnectionPairInterface
{
    /**
     * Construct a new read/write connection pair.
     *
     * @param ConnectionInterface $write The write connection.
     * @param ConnectionInterface $read  The read connection.
     */
    public function __construct(
        ConnectionInterface $write,
        ConnectionInterface $read
    ) {
        $this->write = $write;
        $this->read = $read;
    }

    /**
     * Get the write connection.
     *
     * @return ConnectionInterface The write connection.
     */
    public function write()
    {
        return $this->write;
    }

    /**
     * Get the read connection.
     *
     * @return ConnectionInterface The read connection.
     */
    public function read()
    {
        return $this->read;
    }

    private $write;
    private $read;
}
