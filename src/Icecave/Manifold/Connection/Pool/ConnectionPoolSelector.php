<?php
namespace Icecave\Manifold\Connection\Pool;

use Icecave\Collections\Map;

/**
 * Selects appropriate connection pools for reading and writing based upon
 * configuration.
 */
class ConnectionPoolSelector implements ConnectionPoolSelectorInterface
{
    /**
     * Construct a new connection pool selector.
     *
     * @param ConnectionPoolPairInterface             $defaults  The default read/write pair.
     * @param Map<string,ConnectionPoolPairInterface> $databases Read/write pairs for specific databases.
     *
     * @throws Exception\InvalidDefaultConnectionPoolPairException If the default read/write pair is missing concrete values.
     */
    public function __construct(
        ConnectionPoolPairInterface $defaults,
        Map $databases = null
    ) {
        if (null === $defaults->read() || null === $defaults->write()) {
            throw new Exception\InvalidDefaultConnectionPoolPairException(
                $defaults
            );
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
     * @return ConnectionPoolPairInterface The default read/write pair.
     */
    public function defaults()
    {
        return $this->defaults;
    }

    /**
     * Get the read/write pairs for specific databases.
     *
     * @return Map<string,ConnectionPoolPairInterface> The read/write pairs.
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
        $readWritePair = $this->selectConnectionPoolPair($databaseName);
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
        $readWritePair = $this->selectConnectionPoolPair($databaseName);
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
     * @return ConnectionPoolPairInterface The most appropriate read/write pair.
     */
    public function readWritePair($databaseName = null)
    {
        return new ConnectionPoolPair(
            $this->forWrite($databaseName),
            $this->forRead($databaseName)
        );
    }

    /**
     * Select the most appropriate read/write connection pool pair for the
     * specified database.
     *
     * This method is not guaranteed to return a pair with concrete values. That
     * is, the read or write connection may be null, indicating that it should
     * fall back to the default.
     *
     * @param string|null $databaseName The name of the database, or null for a generic connection.
     *
     * @return ConnectionPoolPairInterface The read/write pair.
     */
    protected function selectConnectionPoolPair($databaseName = null)
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
