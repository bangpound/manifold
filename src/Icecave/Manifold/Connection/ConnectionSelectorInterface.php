<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by connection selectors.
 */
interface ConnectionSelectorInterface
{
    /**
     * Get the connection pool to use for writing the specified database.
     *
     * @param string|null $databaseName The name of the database to write to, or null for a generic connection.
     *
     * @return ConnectionPoolInterface The most appropriate connection pool.
     */
    public function forWrite($databaseName = null);

    /**
     * Get the connection pool to use for reading the specified database.
     *
     * @param string|null $databaseName The name of the database to read from, or null for a generic connection.
     *
     * @return ConnectionPoolInterface The most appropriate connection pool.
     */
    public function forRead($databaseName = null);

    /**
     * Get the read/write connection pool pair for the specified database.
     *
     * @param string|null $databaseName The name of the database, or null for a generic connection pair.
     *
     * @return ReadWritePairInterface The read/write pair.
     */
    public function readWritePair($databaseName = null);
}
