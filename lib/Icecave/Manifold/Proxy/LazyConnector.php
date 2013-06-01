<?php
namespace Icecave\Manifold\Proxy;

use ReflectionClass;

class LazyConnector extends AbstractProxy
{
    public function __construct(array $pdoArguments, ReflectionClass $reflector = null)
    {
        if (null === $reflector) {
            $reflector = new ReflectionClass('PDO');
        }

        $this->pdoArguments = $pdoArguments;
        $this->connection = null;
        $this->reflector = $reflector;
    }

    public function innerConnection()
    {
        if (null === $this->connection) {
            $this->connect();
        }

        return $this->connection;
    }

    public function connect()
    {
        $this->connection = $this->reflector->newInstanceArgs($this->pdoArguments);
    }

    public function isConnected()
    {
        return null !== $this->connection;
    }

    private $pdoArguments;
    private $connection;
    private $reflector;
}
