<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\ConnectionPairInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * The interface implemented by connection selectors.
 */
interface ConnectionSelectorInterface extends LoggerAwareInterface
{
    /**
     * Set the default selection strategy for write statements.
     *
     * @param SelectionStrategy\SelectionStrategyInterface $defaultWriteStrategy The default selection strategy to use for write statements.
     */
    public function setDefaultWriteStrategy(
        SelectionStrategy\SelectionStrategyInterface $defaultWriteStrategy
    );

    /**
     * Get the default selection strategy for write statements.
     *
     * @return SelectionStrategy\SelectionStrategyInterface The default selection strategy for write statements.
     */
    public function defaultWriteStrategy();

    /**
     * Set the default selection strategy for read statements.
     *
     * @param SelectionStrategy\SelectionStrategyInterface $defaultReadStrategy The default selection strategy to use for read statements.
     */
    public function setDefaultReadStrategy(
        SelectionStrategy\SelectionStrategyInterface $defaultReadStrategy
    );

    /**
     * Get the default selection strategy for read statements.
     *
     * @return SelectionStrategy\SelectionStrategyInterface The default selection strategy for read statements.
     */
    public function defaultReadStrategy();

    /**
     * Get the logger.
     *
     * @return LoggerInterface|null The logger, or null if no logger is in use.
     */
    public function logger();

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
    );

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
    );

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
    );
}
