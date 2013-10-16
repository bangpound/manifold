<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\TypeCheck\TypeCheck;
use PDO;

/**
 * Creates connections.
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * Construct a new connection factory.
     *
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
     * @param string      $dsn      The data source name.
     * @param string|null $username The username.
     * @param string|null $password The password.
     *
     * @return PDO The newly created connection.
     */
    public function create($dsn, $username = null, $password = null)
    {
        $this->typeCheck->create(func_get_args());

        return new LazyPdoConnection(
            $dsn,
            $username,
            $password,
            $this->driverOptions()
        );
    }

    private $driverOptions;
    private $typeCheck;
}
