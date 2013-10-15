<?php
namespace Icecave\Manifold\Pdo;

use Icecave\Manifold\TypeCheck\TypeCheck;
use PDO;
use PDOStatement;

abstract class AbstractPdoProxy extends PDO
{
    public function __construct()
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        // Do not call PDO constructor.
    }

    /**
     * Execute an SQL statement.
     *
     * @param string $statement The SQL statement to execute.
     *
     * @return integer|false The number of rows affected by the statement.
     */
    public function exec($statement)
    {
        $this->typeCheck->exec(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'exec'),
            func_get_args()
        );
    }

    /**
     * Prepare a statement for execution.
     *
     * @param string $statement     The SQL statement to prepare.
     * @param mixed  $driverOptions Driver specific options for the prepared statement.
     *
     * @return PDOStatement|false
     */
    public function prepare($statement, $driverOptions = null)
    {
        $this->typeCheck->prepare(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'prepare'),
            func_get_args()
        );
    }

    /**
     * Execute an SQL statement and return the results.
     *
     * See {@link http://www.php.net/manual/en/pdo.query.php} for full information on the accepted parameters.
     *
     * @param string $statement The SQL statement to execute.
     *
     * @return PDOStatement|false
     */
    public function query($statement)
    {
        // Typhoon check intentionally skipped, as the signature for this method is variable.
        return call_user_func_array(
            array($this->innerConnection(), 'query'),
            func_get_args()
        );
    }

    /**
     * Escape a string for use in an SQL statement.
     *
     * @param string $string        The string to escape.
     * @param mixed  $parameterType Data type hint for drivers that have alternate quoting styles.
     *
     * @return string The escaped string.
     */
    public function quote($string, $parameterType = self::PARAM_STR)
    {
        $this->typeCheck->quote(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'quote'),
            func_get_args()
        );
    }

    /**
     * Return the identifier of the last inserted row.
     *
     * @param string|null $name The name of the sequence object, or null if the driver does not use sequences.
     *
     * @return string
     */
    public function lastInsertId($name = NULL)
    {
        $this->typeCheck->lastInsertId(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'lastInsertId'),
            func_get_args()
        );
    }

    /**
     * Begin a transaction.
     *
     * @return boolean True if successful; otherwise, false.
     */
    public function beginTransaction()
    {
        $this->typeCheck->beginTransaction(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'beginTransaction'),
            func_get_args()
        );
    }

    /**
     * Commit the current transaction.
     *
     * @return boolean True if successful; otherwise, false.
     */
    public function commit()
    {
        $this->typeCheck->commit(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'commit'),
            func_get_args()
        );
    }

    /**
     * Rollback the current transaction.
     *
     * @return boolean True if successful; otherwise, false.
     */
    public function rollBack()
    {
        $this->typeCheck->rollBack(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'rollBack'),
            func_get_args()
        );
    }

    /**
     * Check if a transaction is currently in progress.
     *
     * @return boolean True if currently in a transaction; otherwise false.
     */
    public function inTransaction()
    {
        $this->typeCheck->inTransaction(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'inTransaction'),
            func_get_args()
        );
    }

    /**
     * Fetch SQLSTATE code associated with the last error on this connection.
     *
     * @return string|null
     */
    public function errorCode()
    {
        $this->typeCheck->errorCode(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'errorCode'),
            func_get_args()
        );
    }

    /**
     * Return information about the last error on this connection.
     *
     * @return tuple<string,mixed,string|null> A 3-tuple containing the SQLSTATE error code, a driver specific error code, and message.
     */
    public function errorInfo()
    {
        $this->typeCheck->errorInfo(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'errorInfo'),
            func_get_args()
        );
    }

    /**
     * Get an attribute of the connection.
     *
     * @param mixed $attribute The key of the attribute.
     *
     * @return mixed The value of the attribute specified by $attribute.
     */
    public function getAttribute($attribute)
    {
        $this->typeCheck->getAttribute(func_get_args());

        return call_user_func_array(
            array($this->innerConnection(), 'getAttribute'),
            func_get_args()
        );
    }

    /**
     * Set an attribute on the connection.
     *
     * @param mixed $attribute The key of the attribute.
     * @param mixed $value     The value of the attribute specified by $attribute.
     */
    public function setAttribute($attribute, $value)
    {
        $this->typeCheck->setAttribute(func_get_args());

        call_user_func_array(
            array($this->innerConnection(), 'setAttribute'),
            func_get_args()
        );
    }

    abstract public function innerConnection();

    private $typeCheck;
}
