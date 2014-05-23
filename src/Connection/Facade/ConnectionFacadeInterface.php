<?php
namespace Icecave\Manifold\Connection\Facade;

use Icecave\Manifold\Connection\PdoConnectionInterface;
use Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * The interface implemented by PDO connection facades.
 */
interface ConnectionFacadeInterface extends
    PdoConnectionInterface,
    LoggerAwareInterface
{
    /**
     * Get the connection attributes.
     *
     * @return array<integer,mixed> The connection attributes.
     */
    public function attributes();

    /**
     * Set the default selection strategy for write statements.
     *
     * @param SelectionStrategyInterface $defaultWriteStrategy The default selection strategy to use for write statements.
     */
    public function setDefaultWriteStrategy(
        SelectionStrategyInterface $defaultWriteStrategy
    );

    /**
     * Get the default selection strategy for write statements.
     *
     * @return SelectionStrategyInterface The default selection strategy for write statements.
     */
    public function defaultWriteStrategy();

    /**
     * Set the default selection strategy for read statements.
     *
     * @param SelectionStrategyInterface $defaultReadStrategy The default selection strategy to use for read statements.
     */
    public function setDefaultReadStrategy(
        SelectionStrategyInterface $defaultReadStrategy
    );

    /**
     * Get the default selection strategy for read statements.
     *
     * @return SelectionStrategyInterface The default selection strategy for read statements.
     */
    public function defaultReadStrategy();

    /**
     * Get the logger.
     *
     * @return LoggerInterface|null The logger, or null if no logger is in use.
     */
    public function logger();

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

    /**
     * Push a string onto an internal stack, to be prefixed to subsequent
     * queries as a comment.
     *
     * This feature can be useful for tracking the source of queries in SQL
     * logs.
     *
     * @param string $comment      The comment to prefix. Accepts printf-style placeholders.
     * @param mixed  $argument,... Additional arguments for printf-style substitution into $comment.
     */
    public function pushComment($comment);

    /**
     * Pop a string off the internal stack of query comment prefixes.
     *
     * @return string|null The removed comment, or null if the stack is empty.
     */
    public function popComment();
}
