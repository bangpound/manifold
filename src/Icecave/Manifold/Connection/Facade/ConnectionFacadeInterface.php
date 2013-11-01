<?php
namespace Icecave\Manifold\Connection\Facade;

use Icecave\Manifold\Connection\PdoConnectionInterface;
use Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface;
use PDO;
use PDOException;
use PDOStatement;

/**
 * The interface implemented by PDO connection facades.
 */
interface ConnectionFacadeInterface extends PdoConnectionInterface
{
    /**
     * Get the connection attributes.
     *
     * @return array<integer,mixed> The connection attributes.
     */
    public function attributes();

    /**
     * Set the default selection strategy.
     *
     * @param SelectionStrategyInterface $defaultStrategy The default selection strategy to use.
     */
    public function setDefaultStrategy(
        SelectionStrategyInterface $defaultStrategy
    );

    /**
     * Get the default selection strategy.
     *
     * @return SelectionStrategyInterface The default selection strategy.
     */
    public function defaultStrategy();

    /**
     * Prepare an SQL statement to be executed using a connection selection
     * strategy.
     *
     * @link http://php.net/pdo.prepare
     *
     * @param SelectionStrategyInterface $strategy   The strategy to use.
     * @param string                     $statement  The statement to prepare.
     * @param array<integer,mixed>       $attributes The connection attributes to use.
     *
     * @return PDOStatement The prepared PDO statement.
     * @throws PDOException If the statement cannot be prepared.
     */
    public function prepareWithStrategy(
        SelectionStrategyInterface $strategy,
        $statement,
        array $attributes = null
    );

    /**
     * Execute an SQL statement and return the result set using a connection
     * selection strategy.
     *
     * There are a number of valid ways to call this method. See the PHP manual
     * entry for PDO::query() for more information.
     *
     * @link http://php.net/pdo.query
     *
     * @param SelectionStrategyInterface $strategy     The strategy to use.
     * @param string                     $statement    The statement to execute.
     * @param mixed                      $argument,... Additional arguments.
     *
     * @return PDOStatement The result set.
     * @throws PDOException If the statement cannot be executed.
     */
    public function queryWithStrategy(
        SelectionStrategyInterface $strategy,
        $statement
    );

    /**
     * Execute an SQL statement and return the number of rows affected using a
     * connection selection strategy.
     *
     * @link http://php.net/pdo.exec
     *
     * @param SelectionStrategyInterface $strategy  The strategy to use.
     * @param string                     $statement The statement to execute.
     *
     * @return integer      The number of affected rows.
     * @throws PDOException If the statement cannot be executed.
     */
    public function execWithStrategy(
        SelectionStrategyInterface $strategy,
        $statement
    );
}
