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
     * @param string      $dsn      The data source name.
     * @param string|null $username The username.
     * @param string|null $password The password.
     *
     * @return PDO The newly created connection.
     */
    public function create($dsn, $username = null, $password = null);
}
