<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\ConnectionPair;
use Icecave\Manifold\Connection\ConnectionPairInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolSelectorInterface;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use PDO;

/**
 * Selects a single connection, taking into account a replication hierarchy, the
 * current replication delay, and other factors.
 */
class ConnectionSelector implements ConnectionSelectorInterface
{
    /**
     * Construct a new connection selector.
     *
     * @param ConnectionPoolSelectorInterface                   $poolSelector       The connection pool selector to use.
     * @param ReplicationManagerInterface                       $replicationManager The replication manager to use.
     * @param SelectionStrategy\SelectionStrategyInterface|null $defaultStrategy    The default selection strategy to use.
     * @param QueryDiscriminatorInterface|null                  $queryDiscriminator The query discriminator to use.
     */
    public function __construct(
        ConnectionPoolSelectorInterface $poolSelector,
        ReplicationManagerInterface $replicationManager,
        SelectionStrategy\SelectionStrategyInterface $defaultStrategy = null,
        QueryDiscriminatorInterface $queryDiscriminator = null
    ) {
        if (null === $defaultStrategy) {
            $defaultStrategy = new SelectionStrategy\AcceptableDelayStrategy;
        }
        if (null === $queryDiscriminator) {
            $queryDiscriminator = new QueryDiscriminator;
        }

        $this->poolSelector = $poolSelector;
        $this->replicationManager = $replicationManager;
        $this->queryDiscriminator = $queryDiscriminator;

        $this->setDefaultStrategy($defaultStrategy);
    }

    /**
     * Get the connection pool selector.
     *
     * @return ConnectionPoolSelectorInterface The connection pool selector.
     */
    public function poolSelector()
    {
        return $this->poolSelector;
    }

    /**
     * Get the replication manager.
     *
     * @return ReplicationManagerInterface The replication manager.
     */
    public function replicationManager()
    {
        return $this->replicationManager;
    }

    /**
     * Get the query discriminator.
     *
     * @return QueryDiscriminatorInterface The query discriminator.
     */
    public function queryDiscriminator()
    {
        return $this->queryDiscriminator;
    }

    /**
     * Set the default selection strategy.
     *
     * @param SelectionStrategy\SelectionStrategyInterface $defaultStrategy The default selection strategy to use.
     */
    public function setDefaultStrategy(
        SelectionStrategy\SelectionStrategyInterface $defaultStrategy
    ) {
        $this->defaultStrategy = $defaultStrategy;
    }

    /**
     * Get the default selection strategy.
     *
     * @return SelectionStrategy\SelectionStrategyInterface The default selection strategy.
     */
    public function defaultStrategy()
    {
        return $this->defaultStrategy;
    }

    /**
     * Select a connection for the supplied query.
     *
     * @param string                                            $query    The query to select a connection for.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy The selection strategy to use.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\UnsupportedQueryException      If the query type is unsupported, or cannot be determined.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function forQuery(
        $query,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    ) {
        list($isWrite, $databaseName) = $this->queryDiscriminator()
            ->discriminate($query);

        if ($isWrite) {
            $connection = $this->forWrite($databaseName, $strategy);
        } else {
            $connection = $this->forRead($databaseName, $strategy);
        }

        return $connection;
    }

    /**
     * Get the connection to use for writing the specified database.
     *
     * @param string|null                                       $databaseName The name of the database to write to, or null for a generic connection.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy     The selection strategy to use.
     *
     * @return PDO                                      The most appropriate connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function forWrite(
        $databaseName = null,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    ) {
        if (null === $strategy) {
            $strategy = $this->defaultStrategy();
        }

        return $strategy->select(
            $this->replicationManager(),
            $this->poolSelector()->forWrite($databaseName)
        );
    }

    /**
     * Get the connection to use for reading the specified database.
     *
     * @param string|null                                       $databaseName The name of the database to read from, or null for a generic connection.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy     The selection strategy to use.
     *
     * @return PDO                                      The most appropriate connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function forRead(
        $databaseName = null,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    ) {
        if (null === $strategy) {
            $strategy = $this->defaultStrategy();
        }

        return $strategy->select(
            $this->replicationManager(),
            $this->poolSelector()->forRead($databaseName)
        );
    }

    /**
     * Get the read/write connection pair for the specified database.
     *
     * @param string|null                                       $databaseName The name of the database, or null for a generic connection pair.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy     The selection strategy to use.
     *
     * @return ConnectionPairInterface                  The most appropriate read/write pair.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function readWritePair(
        $databaseName = null,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    ) {
        return new ConnectionPair(
            $this->forWrite($databaseName, $strategy),
            $this->forRead($databaseName, $strategy)
        );
    }

    private $poolSelector;
    private $replicationManager;
    private $defaultStrategy;
    private $queryDiscriminator;
}
