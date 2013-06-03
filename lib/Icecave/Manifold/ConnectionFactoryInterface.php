<?php
namespace Icecave\Manifold;

interface ConnectionFactoryInterface
{
    /**
     * @param string      $dsn
     * @param string|null $username
     * @param string|null $password
     *
     * @return Connection
     */
    public function createConnection($dsn, $username = null, $password = null);
}
