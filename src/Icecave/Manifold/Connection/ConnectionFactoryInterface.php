<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by connection factories.
 */
interface ConnectionFactoryInterface
{
    /**
     * Create a connection.
     *
     * @param string          $name     The connection name.
     * @param stringable      $dsn      The data source name.
     * @param stringable|null $username The username.
     * @param stringable|null $password The password.
     *
     * @return PDO The newly created connection.
     */
    public function create($name, $dsn, $username = null, $password = null);
}
