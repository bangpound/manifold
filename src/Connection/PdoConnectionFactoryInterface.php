<?php
namespace Icecave\Manifold\Connection;

use PDO;
use PDOException;

/**
 * The interface implemented by PDO connection factories.
 */
interface PdoConnectionFactoryInterface
{
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
    public function createConnection(
        $dsn,
        $username,
        $password,
        array $attributes
    );
}
