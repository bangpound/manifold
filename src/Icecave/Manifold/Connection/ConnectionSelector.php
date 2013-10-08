<?php
namespace Icecave\Manifold\Connection;

use Icecave\Collections\Map;

/**
 * Selects appropriate connections for reading and writing based upon
 * configuration.
 */
class ConnectionSelector implements ConnectionSelectorInterface
{
    /**
     * Construct a new connection selector.
     *
     * @param ReadWritePairInterface             $defaults  The default read/write pair.
     * @param Map<string,ReadWritePairInterface> $databases Read/write pairs for specific databases.
     *
     * @throws Exception\InvalidDefaultReadWritePairException If the default read/write pair is missing concrete values.
     */
    public function __construct(
        ReadWritePairInterface $defaults,
        Map $databases = null
    ) {
        if (null === $defaults->read() || null === $defaults->write()) {
            throw new Exception\InvalidDefaultReadWritePairException;
        }
        if (null === $databases) {
            $databases = new Map;
        }

        $this->defaults = $defaults;
        $this->databases = $databases;
    }

    /**
     * Get the default read/write pair.
     *
     * @return ReadWritePairInterface The default read/write pair.
     */
    public function defaults()
    {
        return $this->defaults;
    }

    /**
     * Get the read/write pairs for specific databases.
     *
     * @return Map<string,ReadWritePairInterface> The read/write pairs.
     */
    public function databases()
    {
        return $this->databases;
    }

    /**
     * Get the connection pool to use for writing the specified database.
     *
     * @param string|null $databaseName The name of the database to write to, or null for a generic connection.
     *
     * @return ConnectionPoolInterface The most appropriate connection pool.
     */
    public function forWrite($databaseName = null)
    {
        $readWritePair = $this->selectReadWritePair($databaseName);
        if (null === $readWritePair->write()) {
            return $this->defaults()->write();
        }

        return $readWritePair->write();
    }

    /**
     * Get the connection pool to use for reading the specified database.
     *
     * @param string|null $databaseName The name of the database to read from, or null for a generic connection.
     *
     * @return ConnectionPoolInterface The most appropriate connection pool.
     */
    public function forRead($databaseName = null)
    {
        $readWritePair = $this->selectReadWritePair($databaseName);
        if (null === $readWritePair->read()) {
            return $this->defaults()->read();
        }

        return $readWritePair->read();
    }

    /**
     * Get the read/write connection pool pair for the specified database.
     *
     * @param string|null $databaseName The name of the database, or null for a generic connection pair.
     *
     * @return ReadWritePairInterface The read/write pair.
     */
    public function readWritePair($databaseName = null)
    {
        return new ReadWritePair(
            $this->forWrite($databaseName),
            $this->forRead($databaseName)
        );
    }

    /**
     * Select the most appropriate read/write pair for the specified database.
     *
     * This method is not guaranteed to return a pair with concrete values. That
     * is, the read or write connection may be null, indicating that it should
     * fall back to the default.
     *
     * @param string|null $databaseName The name of the database, or null for a generic connection.
     *
     * @return ReadWritePairInterface The read/write pair.
     */
    protected function selectReadWritePair($databaseName = null)
    {
        if (null === $databaseName) {
            return $this->defaults();
        }

        if ($this->databases()->tryGet($databaseName, $readWritePair)) {
            return $readWritePair;
        }

        return $this->defaults();
    }

    private $defaults;
    private $databases;
}
