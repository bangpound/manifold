<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The interface implemented by query connection selectors.
 */
interface QueryConnectionSelectorInterface
{
    /**
     * Get the connection selector.
     *
     * @return ConnectionSelectorInterface The connection selector.
     */
    public function selector();

    /**
     * Select a connection for the supplied query.
     *
     * @param string                                            $query    The query to select a connection for.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy The selection strategy to use.
     *
     * @return tuple<ConnectionInterface,boolean>       A 2-tuple containing the selected connection, and a boolean value indicating if the query is a write query.
     * @throws Exception\UnsupportedQueryException      If the query type is unsupported, or cannot be determined.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        $query,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    );
}
