<?php
namespace Icecave\Manifold\Connection;

use PDO;

/**
 * Creates connections.
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * Construct a new connection factory.
     *
     * @param array|null $attributes The connection attributes to use.
     */
    public function __construct(array $attributes = null)
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the connection attributes.
     *
     * @return array|null The connection attributes.
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Create a connection.
     *
     * @param string      $dsn      The data source name.
     * @param string|null $username The username.
     * @param string|null $password The password.
     *
     * @return PDO The newly created connection.
     */
    public function create($dsn, $username = null, $password = null)
    {
        return new LazyPdoConnection(
            $dsn,
            $username,
            $password,
            $this->attributes()
        );
    }

    private $attributes;
}
