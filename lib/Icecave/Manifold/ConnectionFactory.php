<?php
namespace Icecave\Manifold;

class ConnectionFactory implements ConnectionFactoryInterface
{
    public function __construct(array $driverOptions = array())
    {
        $this->driverOptions = $driverOptions;

        $this->driverOptions += array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_AUTOCOMMIT => false
        );
    }

    /**
     * @param string      $dsn
     * @param string|null $username
     * @param string|null $password
     *
     * @return Connection
     */
    public function createConnection($dsn, $username = null, $password = null)
    {
        return new Connection($dsn, $username, $password, $this->driverOptions);
    }
}
