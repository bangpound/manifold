<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * Wraps a connection selector to implement selection based upon queries.
 */
class QueryConnectionSelector implements QueryConnectionSelectorInterface
{
    /**
     * Construct a new query connection selector.
     *
     * @param ConnectionSelectorInterface      $selector           The connection selector to use.
     * @param QueryDiscriminatorInterface|null $queryDiscriminator The query discriminator to use.
     */
    public function __construct(
        ConnectionSelectorInterface $selector,
        QueryDiscriminatorInterface $queryDiscriminator = null
    ) {
        if (null === $queryDiscriminator) {
            $queryDiscriminator = new QueryDiscriminator;
        }

        $this->selector = $selector;
        $this->queryDiscriminator = $queryDiscriminator;
    }

    /**
     * Get the connection selector.
     *
     * @return ConnectionSelectorInterface The connection selector.
     */
    public function selector()
    {
        return $this->selector;
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
     * Select a connection for the supplied query.
     *
     * @param string                                            $query    The query to select a connection for.
     * @param SelectionStrategy\SelectionStrategyInterface|null $strategy The selection strategy to use.
     *
     * @return ConnectionInterface                      The selected connection.
     * @throws Exception\UnsupportedQueryException      If the query type is unsupported, or cannot be determined.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        $query,
        SelectionStrategy\SelectionStrategyInterface $strategy = null
    ) {
        list($isWrite, $databaseName) = $this->queryDiscriminator()
            ->discriminate($query);

        if ($isWrite) {
            $connection = $this->selector()->forWrite($databaseName, $strategy);
        } else {
            $connection = $this->selector()->forRead($databaseName, $strategy);
        }

        return $connection;
    }

    private $selector;
    private $queryDiscriminator;
}
