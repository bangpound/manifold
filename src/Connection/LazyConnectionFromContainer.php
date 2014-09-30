<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Icecave\Manifold\Replication\SelectionStrategy\AnyStrategy;
use Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * A PDO connection with lazy-connection semantics, where the actual connection
 * is chosen from a specific connection container.
 */
class LazyConnectionFromContainer extends PDO implements ConnectionInterface
{
    /**
     * Construct a new lazy PDO connection that selects from a specific
     * container.
     *
     * @param Container\ConnectionContainerInterface $container          The container to select from.
     * @param ReplicationManagerInterface            $replicationManager The replication manager to use.
     * @param SelectionStrategyInterface|null        $strategy           The connection selection strategy to use.
     * @param array<integer,mixed>|null              $attributes         The connection attributes to use.
     * @param LoggerInterface|null                   $logger             The logger to use.
     */
    public function __construct(
        Container\ConnectionContainerInterface $container,
        ReplicationManagerInterface $replicationManager,
        SelectionStrategyInterface $strategy = null,
        array $attributes = null,
        LoggerInterface $logger = null
    ) {
        if (null === $strategy) {
            $strategy = new AnyStrategy();
        }
        if (null === $attributes) {
            $attributes = array();
        }

        $this->container = $container;
        $this->replicationManager = $replicationManager;
        $this->strategy = $strategy;
        $this->attributes = $attributes;
        $this->logger = $logger;
    }

    /**
     * Get the connection container.
     *
     * @return Container\ConnectionContainerInterface The connection container.
     */
    public function container()
    {
        return $this->container;
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
     * Get the selection strategy.
     *
     * @return SelectionStrategyInterface The selection strategy.
     */
    public function strategy()
    {
        return $this->strategy;
    }

    /**
     * Check if a connection has been established.
     *
     * @return boolean True if a connection has been established.
     */
    public function isConnected()
    {
        return null !== $this->connection;
    }

    /**
     * Establish a connection to the database, if not already connected.
     *
     * @throws PDOException If the connection could not be established.
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return;
        }

        $this->connection = $this->strategy()->select(
            $this->replicationManager(),
            $this->container(),
            $this->logger()
        );

        foreach ($this->attributes() as $attribute => $value) {
            $this->connection->setAttribute($attribute, $value);
        }
    }

    /**
     * Get the real PDO connection.
     *
     * @return PDO          The real connection.
     * @throws PDOException If the connection could not be established.
     */
    public function connection()
    {
        $this->connect();

        return $this->connection;
    }

    // Implementation of ConnectionInterface ===================================

    /**
     * Get the connection name.
     *
     * @return string The connection name.
     */
    public function name()
    {
        return $this->container()->name();
    }

    /**
     * Get the data source name.
     *
     * @return string The data source name.
     */
    public function dsn()
    {
        return $this->connection()->dsn();
    }

    /**
     * Get the connection attributes.
     *
     * @return array<integer,mixed> The connection attributes.
     */
    public function attributes()
    {
        return $this->attributes;
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
        return $this->connection()->prepare($statement, $attributes);
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
        return call_user_func_array(
            array($this->connection(), 'query'),
            func_get_args()
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
        return $this->connection()->exec($statement);
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
        if (!$this->isConnected()) {
            return false;
        }

        return $this->connection()->inTransaction();
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
        return $this->connection()->beginTransaction();
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
        return $this->connection()->commit();
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
        return $this->connection()->rollBack();
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
        if (!$this->isConnected()) {
            return '0';
        }

        return $this->connection()->lastInsertId($name);
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
        if (!$this->isConnected()) {
            return null;
        }

        return $this->connection()->errorCode();
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
        if (!$this->isConnected()) {
            return array('', null, null);
        }

        return $this->connection()->errorInfo();
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
        return $this->connection()->quote($string, $parameterType);
    }

    /**
     * Set the value of an attribute.
     *
     * If a connection has not yet been established, the attribute is set on the
     * internal array.
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
        if (null !== $this->logger()) {
            $this->logger()->debug(
                'Setting attribute {attribute} to {value} on {connection}.',
                array(
                    'attribute' =>
                        PdoConnectionAttribute::memberByValue($attribute)
                        ->qualifiedName(),
                    'value' => $value,
                    'connection' => $this->name(),
                )
            );
        }

        if ($this->isConnected()) {
            $result = $this->connection()->setAttribute($attribute, $value);
        } else {
            $result = true;
        }

        if ($result) {
            $this->attributes[$attribute] = $value;
        }

        return $result;
    }

    /**
     * Get the value of an attribute.
     *
     * If a connection has not yet been established, the attribute is taken from
     * those provided upon construction.
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
        if ($this->isConnected()) {
            return $this->connection()->getAttribute($attribute);
        }

        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }

        return null;
    }

    // Implementation of ConnectionContainerInterface ==========================

    /**
     * Get the connections.
     *
     * @return array<ConnectionInterface> The connections.
     */
    public function connections()
    {
        return array($this);
    }

    // Implementation details ==================================================

    private $container;
    private $replicationManager;
    private $strategy;
    private $attributes;
    private $logger;
    private $connection;
}
