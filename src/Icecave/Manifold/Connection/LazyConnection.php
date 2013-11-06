<?php
namespace Icecave\Manifold\Connection;

use InvalidArgumentException;
use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * A PDO connection with lazy-connection semantics.
 */
class LazyConnection extends PDO implements ConnectionInterface
{
    /**
     * Construct a new lazy PDO connection.
     *
     * @param string                    $name       The connection name.
     * @param stringable                $dsn        The connection data source name.
     * @param stringable|null           $username   The database username, this parameter is optional for some PDO drivers.
     * @param stringable|null           $password   The database password, this parameter is optional for some PDO drivers.
     * @param array<integer,mixed>|null $attributes The connection attributes to use.
     * @param LoggerInterface|null      $logger     The logger to use.
     */
    public function __construct(
        $name,
        $dsn,
        $username = null,
        $password = null,
        array $attributes = null,
        LoggerInterface $logger = null
    ) {
        if (null === $attributes) {
            $attributes = array();
        }
        if (null === $logger) {
            $logger = new NullLogger;
        }

        $this->name = $name;
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->attributes = $attributes;
        $this->logger = $logger;
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

        $this->beforeConnect();

        $this->logger()->debug(
            'Establishing connection {connection} to {dsn}.',
            array(
                'connection' => var_export($this->name(), true),
                'dsn' => var_export($this->dsn(), true),
            )
        );

        $this->connection = $this->createConnection(
            $this->resolveStringable($this->dsn()),
            $this->resolveStringable($this->username()),
            $this->resolveStringable($this->password()),
            $this->attributes()
        );

        $this->afterConnect();
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
        return $this->name;
    }

    /**
     * Get the data source name.
     *
     * @return stringable The data source name.
     */
    public function dsn()
    {
        return $this->dsn;
    }

    /**
     * Get the username.
     *
     * @return stringable|null The username, or null if no username is in use.
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * Get the password.
     *
     * @return stringable|null The password, or null if no password is in use.
     */
    public function password()
    {
        return $this->password;
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
     * @param LoggerInterface $logger The logger to use.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface The logger.
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
        $this->logger()->debug(
            'Preparing statement {statement} on {connection}.',
            array(
                'statement' => var_export($statement, true),
                'connection' => var_export($this->name(), true),
            )
        );

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
        $arguments = func_get_args();
        if (!array_key_exists(0, $arguments)) {
            throw new InvalidArgumentException(
                'PDO::query() expects at least 1 parameter, 0 given'
            );
        }

        $this->logger()->debug(
            'Querying statement {statement} on {connection}.',
            array(
                'statement' => var_export($arguments[0], true),
                'connection' => var_export($this->name(), true),
            )
        );

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
        $this->logger()->debug(
            'Executing statement {statement} on {connection}.',
            array(
                'statement' => var_export($statement, true),
                'connection' => var_export($this->name(), true),
            )
        );

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
        $this->logger()->debug(
            'Beginning transaction on {connection}.',
            array('connection' => var_export($this->name(), true))
        );

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
        $this->logger()->debug(
            'Committing transaction on {connection}.',
            array('connection' => var_export($this->name(), true))
        );

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
        $this->logger()->debug(
            'Rolling back transaction on {connection}.',
            array('connection' => var_export($this->name(), true))
        );

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
        $this->logger()->debug(
            'Setting attribute {attribute} to {value} on {connection}.',
            array(
                'attribute' => var_export($attribute, true),
                'value' => var_export($value, true),
                'connection' => var_export($this->name(), true),
            )
        );

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

    // Implementation details ==================================================

    /**
     * Called before establishing a connection.
     *
     * The default implementation is a no-op, this method may be overridden to
     * provide custom behaviour.
     */
    protected function beforeConnect()
    {
    }

    /**
     * Called after establishing a connection.
     *
     * The default implementation is a no-op, this method may be overridden to
     * provide custom behaviour.
     */
    protected function afterConnect()
    {
    }

    /**
     * Resolves a stringable value into a string.
     *
     * @param stringable|null $value The value to resolve.
     *
     * @return string|null The resolved value.
     */
    protected function resolveStringable($value)
    {
        if (null === $value) {
            return null;
        }

        return strval($value);
    }

    /**
     * Creates a real PDO connection.
     *
     * @param string               $dsn        The data source name.
     * @param string|null          $username   The username, or null if no username should be specified.
     * @param string|null          $password   The password, or null if no password should be specified.
     * @param array<integer,mixed> $attributes The connection attributes to use.
     *
     * @return PDO          The newly created connection.
     * @throws PDOException If the connection could not be established.
     */
    protected function createConnection(
        $dsn,
        $username,
        $password,
        array $attributes
    ) {
        return new PDO($dsn, $username, $password, $attributes);
    }

    private $dsn;
    private $username;
    private $password;
    private $attributes;
    private $logger;
    private $connection;
}
