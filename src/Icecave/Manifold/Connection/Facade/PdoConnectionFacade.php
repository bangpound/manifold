<?php
namespace Icecave\Manifold\Connection\Facade;

use Icecave\Collections\Map;
use Icecave\Collections\Set;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\PdoConnectionInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\Exception\UnsupportedQueryException;
use Icecave\Manifold\Replication\QueryConnectionSelectorInterface;
use Icecave\Manifold\Replication\SelectionStrategy\AnyStrategy;
use Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * The interface implemented by PDO connection facades.
 */
class PdoConnectionFacade extends PDO implements PdoConnectionFacadeInterface
{
    /**
     * Construct a new PDO connection facade.
     *
     * @param QueryConnectionSelectorInterface $queryConnectionSelector The query connection selector to use.
     * @param Map<integer,mixed>|null          $attributes              The connection attributes to use.
     */
    public function __construct(
        QueryConnectionSelectorInterface $queryConnectionSelector,
        Map $attributes = null
    ) {
        if (null === $attributes) {
            $attributes = new Map;
            $attributes->set(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $attributes->set(PDO::ATTR_PERSISTENT, false);
            $attributes->set(PDO::ATTR_AUTOCOMMIT, false);
        } else {
            $attributes = clone $attributes;
        }

        $this->queryConnectionSelector = $queryConnectionSelector;
        $this->attributes = $attributes;

        $this->initializedConnections = new Set;
        $this->isInTransaction = false;
        $this->transactionConnections = new Vector;
    }

    /**
     * Get the query connection selector.
     *
     * @return QueryConnectionSelectorInterface The query connection selector.
     */
    public function queryConnectionSelector()
    {
        return $this->queryConnectionSelector;
    }

    /**
     * Get the connection selector.
     *
     * @return ConnectionSelectorInterface The connection selector.
     */
    public function connectionSelector()
    {
        return $this->queryConnectionSelector()->selector();
    }

    // Implementation of PdoConnectionFacadeInterface ==========================

    /**
     * Get the connection attributes.
     *
     * @return Map<integer,mixed> The connection attributes.
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Set the default selection strategy.
     *
     * @param SelectionStrategyInterface $defaultStrategy The default selection strategy to use.
     */
    public function setDefaultStrategy(
        SelectionStrategyInterface $defaultStrategy
    ) {
        $this->connectionSelector()->setDefaultStrategy($defaultStrategy);
    }

    /**
     * Get the default selection strategy.
     *
     * @return SelectionStrategyInterface The default selection strategy.
     */
    public function defaultStrategy()
    {
        return $this->connectionSelector()->defaultStrategy();
    }

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
    ) {
        if (null === $attributes) {
            $attributes = array();
        }

        return $this->selectConnectionForStatement($statement, $strategy)
            ->prepare($statement, $attributes);
    }

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
    ) {
        $connection = $this->selectConnectionForStatement(
            $statement,
            $strategy
        );

        $arguments = func_get_args();
        array_shift($arguments);

        return call_user_func_array(array($connection, 'query'), $arguments);
    }

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
    ) {
        return $this->selectConnectionForStatement($statement, $strategy)
            ->exec($statement);
    }

    // Implementation of PdoConnectionInterface ================================

    /**
     * Prepare an SQL statement to be executed.
     *
     * @link http://php.net/pdo.prepare
     *
     * @param string               $statement  The statement to prepare.
     * @param array<integer,mixed> $attributes The connection attributes to use.
     *
     * @return PDOStatement The prepared PDO statement.
     * @throws PDOException If the statement cannot be prepared.
     */
    public function prepare($statement, $attributes = array())
    {
        return $this->selectConnectionForStatement($statement)
            ->prepare($statement, $attributes);
    }

    /**
     * Execute an SQL statement and return the result set.
     *
     * There are a number of valid ways to call this method. See the PHP manual
     * entry for PDO::query() for more information.
     *
     * @link http://php.net/pdo.query
     *
     * @param mixed $argument,... Arguments.
     *
     * @return PDOStatement The result set.
     * @throws PDOException If the statement cannot be executed.
     */
    public function query()
    {
        $arguments = func_get_args();
        if (!array_key_exists(0, $arguments)) {
            throw new InvalidArgumentException(
                'PDO::query() expects at least 1 parameter, 0 given'
            );
        }

        return call_user_func_array(
            array($this->selectConnectionForStatement($arguments[0]), 'query'),
            $arguments
        );
    }

    /**
     * Execute an SQL statement and return the number of rows affected.
     *
     * @link http://php.net/pdo.exec
     *
     * @param string $statement The statement to execute.
     *
     * @return integer      The number of affected rows.
     * @throws PDOException If the statement cannot be executed.
     */
    public function exec($statement)
    {
        return $this->selectConnectionForStatement($statement)
            ->exec($statement);
    }

    /**
     * Returns true if there is an active transaction.
     *
     * @link http://php.net/pdo.intransaction
     *
     * @return boolean True if there is an active transaction.
     */
    public function inTransaction()
    {
        return $this->isInTransaction;
    }

    /**
     * Start a transation.
     *
     * @link http://php.net/pdo.begintransaction
     *
     * @return boolean      True if a transaction was started.
     * @throws PDOException If the transaction cannot be started.
     */
    public function beginTransaction()
    {
        if ($this->inTransaction()) {
            throw new Exception\PdoException(
                'There is already an active transaction'
            );
        }

        $this->transactionConnections()->clear();
        $this->isInTransaction = true;

        return true;
    }

    /**
     * Commit the active transaction.
     *
     * @link http://php.net/pdo.commit
     *
     * @return boolean      True if the transaction was successfully committed.
     * @throws PDOException If the transaction cannot be committed.
     */
    public function commit()
    {
        if (!$this->inTransaction()) {
            throw new Exception\PdoException('There is no active transaction');
        }

        $result = true;
        $error = null;
        foreach ($this->transactionConnections() as $connection) {
            try {
                $subResult = $connection->commit();
            } catch (PDOException $e) {
                $subResult = false;
                if (null === $error) {
                    $error = $e;
                }
            }

            $result = $result && $subResult;
        }

        $this->endTransaction();

        if (null !== $error) {
            throw $error;
        }

        return $result;
    }

    /**
     * Roll back the active transaction.
     *
     * @link http://php.net/pdo.rollback
     *
     * @return boolean      True if the transaction was successfully rolled back.
     * @throws PDOException If the transaction cannot be rolled back.
     */
    public function rollBack()
    {
        if (!$this->inTransaction()) {
            throw new Exception\PdoException('There is no active transaction');
        }

        $result = true;
        $error = null;
        foreach ($this->transactionConnections() as $connection) {
            try {
                $subResult = $connection->rollBack();
            } catch (PDOException $e) {
                $subResult = false;
                if (null === $error) {
                    $error = $e;
                }
            }

            $result = $result && $subResult;
        }

        $this->endTransaction();

        if (null !== $error) {
            throw $error;
        }

        return $result;
    }

    /**
     * Get the ID of the last inserted row.
     *
     * @link http://php.net/pdo.lastinsertid
     *
     * @param string|null $name The name of the sequence object to query.
     *
     * @return string The last inserted ID.
     */
    public function lastInsertId($name = null)
    {
        if (null === $this->currentConnection()) {
            return '0';
        }

        return $this->currentConnection()->lastInsertId($name);
    }

    /**
     * Get the most recent status code for this connection.
     *
     * @link http://php.net/pdo.errorcode
     *
     * @return string|null The status code, or null if no statement has been run on this connection.
     */
    public function errorCode()
    {
        if (null === $this->currentConnection()) {
            return null;
        }

        return $this->currentConnection()->errorCode();
    }

    /**
     * Get status information about the last operation performed on this
     * connection.
     *
     * For details of the status information returned, see the PHP manual entry
     * for PDO::errorInfo().
     *
     * @link http://php.net/pdo.errorinfo
     *
     * @return array The status information.
     */
    public function errorInfo()
    {
        if (null === $this->currentConnection()) {
            return array('', null, null);
        }

        return $this->currentConnection()->errorInfo();
    }

    /**
     * Quotes a string using an appropriate quoting style for the underlying driver.
     *
     * @link http://php.net/pdo.quote
     *
     * @param string  $string        The string to quote.
     * @param integer $parameterType The parameter type.
     *
     * @return string       The quoted string.
     * @throws PDOException If the parameter type is not supported.
     */
    public function quote($string, $parameterType = PDO::PARAM_STR)
    {
        $connection = $this->currentConnection();
        if (null === $connection) {
            $connection = $this->selectConnectionForRead(null, new AnyStrategy);
        }

        return $connection->quote($string, $parameterType);
    }

    /**
     * Set the value of an attribute.
     *
     * @link http://php.net/pdo.setattribute
     *
     * @param integer $attribute The attribute to set.
     * @param mixed   $value     The value to set the attribute to.
     *
     * @return boolean      True if the attribute was successfully set.
     * @throws PDOException If the attribute could not be set.
     */
    public function setAttribute($attribute, $value)
    {
        $this->attributes->set($attribute, $value);

        $result = true;
        $error = null;
        foreach ($this->initializedConnections() as $connection) {
            try {
                $subResult = $connection->setAttribute($attribute, $value);
            } catch (PDOException $e) {
                $subResult = false;
                if (null === $error) {
                    $error = $e;
                }
            }

            $result = $result && $subResult;
        }

        if (null !== $error) {
            throw $error;
        }

        return $result;
    }

    /**
     * Get the value of an attribute.
     *
     * @link http://php.net/pdo.getattribute
     *
     * @param integer $attribute The attribute to get.
     *
     * @return mixed        The attribute value.
     * @throws PDOException If the attribute could not be read.
     */
    public function getAttribute($attribute)
    {
        $connection = $this->currentConnection();
        if (null === $connection) {
            $connection = $this->selectConnectionForRead(null, new AnyStrategy);
        }

        return $connection->getAttribute($attribute);
    }

    // Implementation details ==================================================

    /**
     * Get the initialized connections.
     *
     * @return Set<PDO> The initialized connections.
     */
    protected function initializedConnections()
    {
        return $this->initializedConnections;
    }

    /**
     * Set the current concrete connection.
     *
     * @param PDO $currentConnection The connection to use.
     */
    protected function setCurrentConnection(PDO $currentConnection)
    {
        $this->currentConnection = $currentConnection;
    }

    /**
     * Get the most recently used concrete connection.
     *
     * @return PDO|null The most recently used concrete connection, or null if no connection has yet been used.
     */
    protected function currentConnection()
    {
        return $this->currentConnection;
    }

    /**
     * Record the end of a transaction.
     */
    protected function endTransaction()
    {
        $this->isInTransaction = false;
    }

    /**
     * Get the connections involved in the current transaction.
     *
     * @return Vector<PDO> The transaction connections.
     */
    protected function transactionConnections()
    {
        return $this->transactionConnections;
    }

    /**
     * Select a connection for writing and set it as the current connection.
     *
     * @param string|null                     $databaseName The name of the database to write to, or null for a generic connection.
     * @param SelectionStrategyInterface|null $strategy     The strategy to use, or null to use the default strategy.
     *
     * @return PDO          The connection to use.
     * @throws PDOException If a connection cannot be obtained.
     */
    protected function selectConnectionForWrite(
        $databaseName = null,
        SelectionStrategyInterface $strategy = null
    ) {
        try {
            $connection = $this->connectionSelector()
                ->forWrite($databaseName, $strategy);
        } catch (NoConnectionAvailableException $e) {
            throw new Exception\PdoException($e->getMessage(), null, null, $e);
        }

        $this->activateConnection($connection);

        return $connection;
    }

    /**
     * Select a connection for reading and set it as the current connection.
     *
     * @param string|null                     $databaseName The name of the database to read from, or null for a generic connection.
     * @param SelectionStrategyInterface|null $strategy     The strategy to use, or null to use the default strategy.
     *
     * @return PDO          The connection to use.
     * @throws PDOException If a connection cannot be obtained.
     */
    protected function selectConnectionForRead(
        $databaseName = null,
        SelectionStrategyInterface $strategy = null
    ) {
        try {
            $connection = $this->connectionSelector()
                ->forRead($databaseName, $strategy);
        } catch (NoConnectionAvailableException $e) {
            throw new Exception\PdoException($e->getMessage(), null, null, $e);
        }

        $this->activateConnection($connection);

        return $connection;
    }

    /**
     * Select a connection for the supplied statement and set it as the current
     * connection.
     *
     * @param string                          $statement The statement to be executed.
     * @param SelectionStrategyInterface|null $strategy  The strategy to use, or null to use the default strategy.
     *
     * @return PDO          The connection to use.
     * @throws PDOException If a connection cannot be obtained.
     */
    protected function selectConnectionForStatement(
        $statement,
        SelectionStrategyInterface $strategy = null
    ) {
        try {
            $connection = $this->queryConnectionSelector()
                ->select($statement, $strategy);
        } catch (UnsupportedQueryException $e) {
            throw new Exception\PdoException($e->getMessage(), null, null, $e);
        } catch (NoConnectionAvailableException $e) {
            throw new Exception\PdoException($e->getMessage(), null, null, $e);
        }

        $this->activateConnection($connection);

        return $connection;
    }

    /**
     * Initializes the supplied connection, applying any custom attributes
     * before first-time use, and initiating transactions where necessary.
     *
     * This method also records the connection as the 'current' connection.
     *
     * @param PDO $connection The connection to initialize.
     *
     * @throws PDOException If activation fails.
     */
    protected function activateConnection(PDO $connection)
    {
        if (!$this->initializedConnections()->contains($connection)) {
            foreach ($this->attributes() as $attribute => $value) {
                if (!$connection->setAttribute($attribute, $value)) {
                    throw new Exception\PdoException(
                        'Unable to set attribute on child connection.'
                    );
                }
            }

            $this->initializedConnections()->add($connection);
        }

        if (
            $this->inTransaction() &&
            !$this->transactionConnections()->contains($connection)
        ) {
            if (!$connection->beginTransaction()) {
                throw new Exception\PdoException(
                    'Unable to begin transaction on child connection.'
                );
            }
            $this->transactionConnections()->pushBack($connection);
        }

        $this->setCurrentConnection($connection);
    }

    private $selector;
    private $attributes;

    private $initializedConnections;
    private $currentConnection;
    private $isInTransaction;
    private $transactionConnections;
}
