<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by connection pool read/write pairs.
 */
interface ReadWritePairInterface
{
    /**
     * Get the write connection pool.
     *
     * @return ConnectionPoolInterface|null The connection pool, or null if the default should be used.
     */
    public function write();

    /**
     * Get the read connection pool.
     *
     * @return ConnectionPoolInterface|null The connection pool, or null if the default should be used.
     */
    public function read();
}
