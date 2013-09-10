<?php
namespace Icecave\Manifold\Connection;

interface ConnectionFactoryInterface
{
    /**
     * Create a connection.
     *
     * @param string      $dsn
     * @param string|null $username
     * @param string|null $password
     *
     * @return PDO
     */
    public function createConnection($dsn, $username = null, $password = null);
}
