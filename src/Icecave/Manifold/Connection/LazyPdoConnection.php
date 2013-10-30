<?php
namespace Icecave\Manifold\Connection;

use PDO;

/**
 * A PDO connection with lazy-connection semantics.
 */
class LazyPdoConnection extends PDO implements PdoConnectionInterface
{
    /**
     * Construct a new lazy PDO connection.
     *
     * @param string      $dsn           The connection data source name.
     * @param string|null $username      The database username, this parameter is optional for some PDO drivers.
     * @param string|null $password      The database password, this parameter is optional for some PDO drivers.
     * @param array|null  $driverOptions The driver-specific options.
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $driverOptions = null
    ) {
        if (null === $driverOptions) {
            $driverOptions = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_AUTOCOMMIT => false,
            );
        }

        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->driverOptions = $driverOptions;
        $this->isConnected = false;

        // Do not call PDO constructor ...
    }

    /**
     * Get the data source name.
     *
     * @return string The data source name.
     */
    public function dsn()
    {
        return $this->dsn;
    }

    /**
     * Get the username.
     *
     * @return string|null The username, or null if no username is in use.
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * Get the password.
     *
     * @return string The password, or null if no password is in use.
     */
    public function password()
    {
        return $this->password;
    }

    /**
     * Get the driver-specific options for this connection.
     *
     * @return array The driver-specific options.
     */
    public function driverOptions()
    {
        return $this->driverOptions;
    }

    /**
     * Get an attribute of the connection.
     *
     * If a connection has not yet been established, the attribute is taken from the driver options provided upon construction.
     *
     * @param mixed $attribute The key of the attribute.
     *
     * @return mixed The value of the attribute specified by $attribute.
     */
    public function getAttribute($attribute)
    {
        // @codeCoverageIgnoreStart
        if ($this->isConnected()) {
            return parent::getAttribute($attribute);
        }
        // @codeCoverageIgnoreEnd

        if (array_key_exists($attribute, $this->driverOptions)) {
            return $this->driverOptions[$attribute];
        } else {
            return null;
        }
    }

    /**
     * Set an attribute on the connection.
     *
     * If a connection has not yet been established, the attribute is set on the driver options array.
     *
     * @param mixed $attribute The key of the attribute.
     * @param mixed $value     The value of the attribute specified by $attribute.
     */
    public function setAttribute($attribute, $value)
    {
        // @codeCoverageIgnoreStart
        if ($this->isConnected()) {
            parent::setAttribute($attribute, $value);
        }
        // @codeCoverageIgnoreEnd

        $this->driverOptions[$attribute] = $value;
    }

    /**
     * Check if a connection has been established.
     *
     * @return boolean True if a connection has been established; otherwise false.
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * Establish a connection to the database, if not already connected.
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $this->beforeConnect();

            $this->constructParent($this->dsn, $this->username, $this->password, $this->driverOptions);
            $this->isConnected = true;

            $this->afterConnect();
        }
    }

    /**
     * Called before establishing a connection.
     *
     * The default implementation is a no-op, this method may be overridden to provide custom behaviour.
     */
    protected function beforeConnect()
    {
    }

    /**
     * Called after establishing a connection.
     *
     * The default implementation is a no-op, this method may be overridden to provide custom behaviour.
     */
    protected function afterConnect()
    {
    }

    // @codeCoverageIgnoreStart
    /**
     * Call the parent class constructor.
     *
     * @param string      $dsn           The connection data-source name.
     * @param string|null $username      The database username, this parameter is optional for some PDO drivers.
     * @param string|null $password      The database password, this parameter is optional for some PDO drivers.
     * @param array       $driverOptions An associative array of driver-specific options.
     */
    protected function constructParent(
        $dsn,
        $username = null,
        $password = null,
        array $driverOptions = array()
    ) {
        parent::__construct($dsn, $username, $password, $driverOptions);
    }
    // @codeCoverageIgnoreEnd

    private $dsn;
    private $username;
    private $password;
    private $driverOptions;
    private $isConnected;
}
