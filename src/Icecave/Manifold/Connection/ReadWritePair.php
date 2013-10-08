<?php
namespace Icecave\Manifold\Connection;

/**
 * Represents a read/write pair of connection pools.
 */
class ReadWritePair implements ReadWritePairInterface
{
    /**
     * Construct a new read/write connection pool pair.
     *
     * @param ConnectionPoolInterface|null $write The write connection pool, or null if the default should be used.
     * @param ConnectionPoolInterface|null $read  The read connection pool, or null if the default should be used.
     */
    public function __construct(
        ConnectionPoolInterface $write = null,
        ConnectionPoolInterface $read = null
    ) {
        $this->write = $write;
        $this->read = $read;
    }

    /**
     * Get the write connection pool.
     *
     * @return ConnectionPoolInterface|null The connection pool, or null if the default should be used.
     */
    public function write()
    {
        return $this->write;
    }

    /**
     * Get the read connection pool.
     *
     * @return ConnectionPoolInterface|null The connection pool, or null if the default should be used.
     */
    public function read()
    {
        return $this->read;
    }

    private $write;
    private $read;
}
