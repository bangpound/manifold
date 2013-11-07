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
     * Set the default selection strategy.
     *
     * @param SelectionStrategy\SelectionStrategyInterface $defaultStrategy The default selection strategy to use.
     */
    public function setDefaultStrategy(
        SelectionStrategy\SelectionStrategyInterface $defaultStrategy
    );

    /**
     * Get the default selection strategy.
     *
     * @return SelectionStrategy\SelectionStrategyInterface The default selection strategy.
     */
    public function defaultStrategy();

    /**
     * Get the logger.
     *
     * @return LoggerInterface The logger.
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
