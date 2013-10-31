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
     * @param string      $dsn        The connection data source name.
     * @param string|null $username   The database username, this parameter is optional for some PDO drivers.
     * @param string|null $password   The database password, this parameter is optional for some PDO drivers.
     * @param array|null  $attributes The connection attributes to use.
     */
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $attributes = null
    ) {
        if (null === $attributes) {
            $attributes = array();
        }

        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->attributes = $attributes;
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
     * Get the connection attributes.
     *
     * @return array The connection attributes.
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Get an attribute of the connection.
     *
     * If a connection has not yet been established, the attribute is taken from
     * those provided upon construction.
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

        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        } else {
            return null;
        }
    }

    /**
     * Set an attribute on the connection.
     *
     * If a connection has not yet been established, the attribute is set on the
     * internal array.
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

        $this->attributes[$attribute] = $value;
    }

    /**
     * Check if a connection has been established.
     *
     * @return boolean True if a connection has been established.
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

            $this->constructParent($this->dsn, $this->username, $this->password, $this->attributes);
            $this->isConnected = true;

            $this->afterConnect();
        }
    }

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

    // @codeCoverageIgnoreStart
    /**
     * Call the parent class constructor.
     *
     * @param string      $dsn        The connection data-source name.
     * @param string|null $username   The database username, this parameter is optional for some PDO drivers.
     * @param string|null $password   The database password, this parameter is optional for some PDO drivers.
     * @param array       $attributes The connection attributes to use.
     */
    protected function constructParent(
        $dsn,
        $username = null,
        $password = null,
        array $attributes = array()
    ) {
        parent::__construct($dsn, $username, $password, $attributes);
    }
    // @codeCoverageIgnoreEnd

    private $dsn;
    private $username;
    private $password;
    private $attributes;
    private $isConnected;
}
