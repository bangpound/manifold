<?php
namespace Icecave\Manifold\Proxy;

use PDO;

abstract class AbstractProxy extends PDO
{
    public function __construct()
    {
    }

    public function exec($statement)
    {
        return $this->innerConnection()->exec($statement);
    }

    public function prepare($statement, $driverOptions = null)
    {
        return $this->innerConnection()->prepare($statement, $driverOptions);
    }

    public function query($statement)
    {
        return $this->innerConnection()->query($statement);
    }

    public function quote($string, $parameterType = self::PARAM_STR)
    {
        return $this->innerConnection()->quote($string, $parameterType);
    }

    public function lastInsertId($name = NULL)
    {
        return $this->innerConnection()->lastInsertId($name);
    }

    public function beginTransaction()
    {
        $this->innerConnection()->beginTransaction();
    }

    public function commit()
    {
        $this->innerConnection()->commit();
    }

    public function rollBack()
    {
        $this->innerConnection()->rollBack();
    }

    public function inTransaction()
    {
        return $this->innerConnection()->inTransaction();
    }

    public function errorCode()
    {
        return $this->innerConnection()->errorCode();
    }

    public function errorInfo()
    {
        return $this->innerConnection()->errorInfo();
    }

    public function getAttribute($attribute)
    {
        return $this->innerConnection()->getAttribute($attribute);
    }

    public function setAttribute($attribute, $value)
    {
        $this->innerConnection()->setAttribute($attribute, $value);
    }

    abstract public function innerConnection();
}
