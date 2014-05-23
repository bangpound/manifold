<?php
namespace Icecave\Manifold\Connection\Container;

/**
 * The interface implemented by connection container selectors.
 */
interface ConnectionContainerSelectorInterface
{
    /**
     * Get the connection container to use for writing the specified database.
     *
     * @param string|null $databaseName The name of the database to write to, or null for a generic connection.
     *
     * @return ConnectionContainerInterface The most appropriate connection container.
     */
    public function forWrite($databaseName = null);

    /**
     * Get the connection container to use for reading the specified database.
     *
     * @param string|null $databaseName The name of the database to read from, or null for a generic connection.
     *
     * @return ConnectionContainerInterface The most appropriate connection container.
     */
    public function forRead($databaseName = null);

    /**
     * Get the read/write connection container pair for the specified database.
     *
     * @param string|null $databaseName The name of the database, or null for a generic connection pair.
     *
     * @return ConnectionContainerPairInterface The most appropriate read/write pair.
     */
    public function readWritePair($databaseName = null);
}
