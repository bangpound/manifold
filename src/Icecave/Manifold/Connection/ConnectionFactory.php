<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\Pdo\LazyPdo;
use Icecave\Manifold\TypeCheck\TypeCheck;
use PDO;

class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @param array|null $driverOptions Options to pass to the connection upon creation.
     */
    public function __construct(array $driverOptions = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->driverOptions = $driverOptions;
    }

    /**
     * Get the driver-specific options passed to the connection upon creation.
     *
     * @return array|null The driver-specific options.
     */
    public function driverOptions()
    {
        return $this->driverOptions;
    }

    /**
     * Create a connection.
     *
     * @param string      $dsn
     * @param string|null $username
     * @param string|null $password
     *
     * @return PDO
     */
    public function createConnection($dsn, $username = null, $password = null)
    {
        $this->typeCheck->createConnection(func_get_args());

        return new LazyPdo(
            $dsn,
            $username,
            $password,
            $this->driverOptions()
        );
    }

    private $driverOptions;
    private $typeCheck;
}
