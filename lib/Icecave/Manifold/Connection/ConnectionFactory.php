<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\PDO\LazyPDO;
use Icecave\Manifold\TypeCheck\TypeCheck;
use PDO;

class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @param array $driverOptions Options to bass to the connection upon creation.
     */
    public function __construct(array $driverOptions = array())
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->driverOptions = $driverOptions;

        $this->driverOptions += array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_AUTOCOMMIT => false,
        );
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

        return new LazyPDO(
            $dsn,
            $username,
            $password,
            $this->driverOptions
        );
    }

    private $typeCheck;
}
