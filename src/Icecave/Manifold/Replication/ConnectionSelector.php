<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\ConnectionPair;
use Icecave\Manifold\Connection\ConnectionPairInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Selects a single connection, taking into account a replication hierarchy, the
 * current replication delay, and other factors.
 */
class ConnectionSelector implements ConnectionSelectorInterface
{
    /**
     * Construct a new connection selector.
     *
     * @param ConnectionContainerSelectorInterface              $containerSelector    The connection container selector to use.
     * @param ReplicationManagerInterface                       $replicationManager   The replication manager to use.
     * @param SelectionStrategy\SelectionStrategyInterface|null $defaultWriteStrategy The default selection strategy to use for write statements.
     * @param SelectionStrategy\SelectionStrategyInterface|null $defaultReadStrategy  The default selection strategy to use for read statements.
     * @param LoggerInterface|null                              $logger               The logger to use.
     */
    // @codeCoverageIgnoreStart
    public function __construct(
        ConnectionContainerSelectorInterface $containerSelector,
        ReplicationManagerInterface $replicationManager,
        SelectionStrategy\SelectionStrategyInterface $defaultWriteStrategy =
            null,
        SelectionStrategy\SelectionStrategyInterface $defaultReadStrategy =
            null,
        LoggerInterface $logger = null
    ) {
        // @codeCoverageIgnoreEnd
        if (null === $defaultWriteStrategy) {
            $defaultWriteStrategy = new SelectionStrategy\AnyStrategy;
        }
        if (null === $defaultReadStrategy) {
            $defaultReadStrategy =
                new SelectionStrategy\AcceptableDelayStrategy;
        }

        $this->containerSelector = $containerSelector;
        $this->replicationManager = $replicationManager;
        $this->defaultWriteStrategy = $defaultWriteStrategy;
        $this->defaultReadStrategy = $defaultReadStrategy;
        $this->logger = $logger;
    }

    /**
     * Get the connection container selector.
     *
     * @return ConnectionContainerSelectorInterface The connection container selector.
     */
    public function containerSelector()
    {
        return $this->containerSelector;
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
     * Set the default selection strategy for write statements.
     *
     * @param SelectionStrategy\SelectionStrategyInterface $defaultWriteStrategy The default selection strategy to use for write statements.
     */
    public function setDefaultWriteStrategy(
        SelectionStrategy\SelectionStrategyInterface $defaultWriteStrategy
    ) {
        $this->defaultWriteStrategy = $defaultWriteStrategy;
    }

    /**
     * Get the default selection strategy for write statements.
     *
     * @return SelectionStrategy\SelectionStrategyInterface The default selection strategy for write statements.
     */
    public function defaultWriteStrategy()
    {
        return $this->defaultWriteStrategy;
    }

    /**
     * Set the default selection strategy for read statements.
     *
     * @param SelectionStrategy\SelectionStrategyInterface $defaultReadStrategy The default selection strategy to use for read statements.
     */
    public function setDefaultReadStrategy(
        SelectionStrategy\SelectionStrategyInterface $defaultReadStrategy
    ) {
        $this->defaultReadStrategy = $defaultReadStrategy;
    }

    /**
     * Get the default selection strategy for read statements.
     *
     * @return SelectionStrategy\SelectionStrategyInterface The default selection strategy for read statements.
     */
    public function defaultReadStrategy()
    {
        return $this->defaultReadStrategy;
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface|null $logger The logger to use, or null to remove the current logger.
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface|null The logger, or null if no logger is in use.
     */
    public function logger()
    {
        return $this->logger;
    }

    /**
     * Get the connection to use for writing the specified database.
     *
     * @param string|null                                       $databaseName The name of the database to write to, or null for a generic connection.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy     The selection strategy to use.
     *
     * @return ConnectionInterface                      The most appropriate connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function forWrite(
        $databaseName = null,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    ) {
        if (null === $strategy) {
            $strategy = $this->defaultWriteStrategy();
        }

        return $strategy->select(
            $this->replicationManager(),
            $this->containerSelector()->forWrite($databaseName),
            $this->logger()
        );
    }

    /**
     * Get the connection to use for reading the specified database.
     *
     * @param string|null                                       $databaseName The name of the database to read from, or null for a generic connection.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy     The selection strategy to use.
     *
     * @return ConnectionInterface                      The most appropriate connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function forRead(
        $databaseName = null,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    ) {
        if (null === $strategy) {
            $strategy = $this->defaultReadStrategy();
        }

        return $strategy->select(
            $this->replicationManager(),
            $this->containerSelector()->forRead($databaseName),
            $this->logger()
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

    private $containerSelector;
    private $replicationManager;
    private $defaultWriteStrategy;
    private $defaultReadStrategy;
    private $logger;
}
