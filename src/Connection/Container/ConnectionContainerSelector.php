<?php
namespace Icecave\Manifold\Connection\Container;

/**
 * Selects appropriate connection containers for reading and writing based upon
 * configuration.
 */
class ConnectionContainerSelector implements
    ConnectionContainerSelectorInterface
{
    /**
     * Construct a new connection container selector.
     *
     * @param ConnectionContainerPairInterface               $defaults  The default read/write pair.
     * @param array<string,ConnectionContainerPairInterface> $databases Read/write pairs for specific databases.
     *
     * @throws Exception\InvalidDefaultConnectionContainerPairException If the default read/write pair is missing concrete values.
     */
    public function __construct(
        ConnectionContainerPairInterface $defaults,
        array $databases = null
    ) {
        if (null === $defaults->read() || null === $defaults->write()) {
            throw new Exception\InvalidDefaultConnectionContainerPairException(
                $defaults
            );
        }
        if (null === $databases) {
            $databases = array();
        }

        $this->defaults = $defaults;
        $this->databases = $databases;
    }

    /**
     * Get the default read/write pair.
     *
     * @return ConnectionContainerPairInterface The default read/write pair.
     */
    public function defaults()
    {
        return $this->defaults;
    }

    /**
     * Get the read/write pairs for specific databases.
     *
     * @return array<string,ConnectionContainerPairInterface> The read/write pairs.
     */
    public function databases()
    {
        return $this->databases;
    }

    /**
     * Get the connection container to use for writing the specified database.
     *
     * @param string|null $databaseName The name of the database to write to, or null for a generic connection.
     *
     * @return ConnectionContainerInterface The most appropriate connection container.
     */
    public function forWrite($databaseName = null)
    {
        $readWritePair = $this->selectConnectionContainerPair($databaseName);
        if (null === $readWritePair->write()) {
            return $this->defaults()->write();
        }

        return $readWritePair->write();
    }

    /**
     * Get the connection container to use for reading the specified database.
     *
     * @param string|null $databaseName The name of the database to read from, or null for a generic connection.
     *
     * @return ConnectionContainerInterface The most appropriate connection container.
     */
    public function forRead($databaseName = null)
    {
        $readWritePair = $this->selectConnectionContainerPair($databaseName);
        if (null === $readWritePair->read()) {
            return $this->defaults()->read();
        }

        return $readWritePair->read();
    }

    /**
     * Get the read/write connection container pair for the specified database.
     *
     * @param string|null $databaseName The name of the database, or null for a generic connection pair.
     *
     * @return ConnectionContainerPairInterface The most appropriate read/write pair.
     */
    public function readWritePair($databaseName = null)
    {
        return new ConnectionContainerPair(
            $this->forWrite($databaseName),
            $this->forRead($databaseName)
        );
    }

    /**
     * Select the most appropriate read/write connection container pair for the
     * specified database.
     *
     * This method is not guaranteed to return a pair with concrete values. That
     * is, the read or write connection may be null, indicating that it should
     * fall back to the default.
     *
     * @param string|null $databaseName The name of the database, or null for a generic connection.
     *
     * @return ConnectionContainerPairInterface The read/write pair.
     */
    protected function selectConnectionContainerPair($databaseName = null)
    {
        if (null === $databaseName) {
            return $this->defaults();
        }

        $databases = $this->databases();
        if (array_key_exists($databaseName, $databases)) {
            return $databases[$databaseName];
        }

        return $this->defaults();
    }

    private $defaults;
    private $databases;
}
