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
     * @param array<integer,mixed>|null $attributes The connection attributes to use.
     */
    public function __construct(array $attributes = null)
    {
        if (null === $attributes) {
            $attributes = array(
                PDO::ATTR_PERSISTENT => false,
            );
        }

        $this->attributes = $attributes;
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
     * Create a connection.
     *
     * @param string          $name     The connection name.
     * @param stringable      $dsn      The data source name.
     * @param stringable|null $username The username.
     * @param stringable|null $password The password.
     *
     * @return ConnectionInterface The newly created connection.
     */
    public function create($name, $dsn, $username = null, $password = null)
    {
        return new LazyConnection(
            $name,
            $dsn,
            $username,
            $password,
            $this->attributes()
        );
    }

    private $attributes;
}
