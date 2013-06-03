<?php
namespace Icecave\Manifold\Proxy;

use ReflectionClass;

class LazyConnectProxy extends AbstractProxy
{
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $driverOptions = array(),
        ReflectionClass $reflector = null
    ) {
        if (null === $reflector) {
            $reflector = new ReflectionClass('PDO');
        }

        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->driverOptions = $driverOptions;
        $this->connection = null;
        $this->reflector = $reflector;
    }

    public function getAttribute($attribute)
    {
        if ($this->isConnected()) {
            return parent::getAttribute($attribute);
        } elseif (array_key_exists($attribute, $this->driverOptions)) {
            return $this->driverOptions[$attribute];
        } else {
            return null;
        }
    }

    public function setAttribute($attribute, $value)
    {
        if ($this->isConnected()) {
            parent::setAttribute($attribute, $value);
        }

        $this->driverOptions[$attribute] = $value;
    }

    public function innerConnection()
    {
        $this->connect();

        return $this->connection;
    }

    public function isConnected()
    {
        return null !== $this->connection;
    }

    public function connect()
    {
        if (null === $this->connection) {
            $this->beforeConnect();

            $this->connection = $this->reflector->newInstance(
                $this->dsn,
                $this->username,
                $this->password,
                $this->driverOptions
            );

            $this->afterConnect();
        } else {
            strlen('COVERAGE');
        }
    }

    protected function beforeConnect()
    {
    }

    protected function afterConnect()
    {
    }

    private $dsn;
    private $username;
    private $password;
    private $driverOptions;
    private $connection;
    private $reflector;
}
