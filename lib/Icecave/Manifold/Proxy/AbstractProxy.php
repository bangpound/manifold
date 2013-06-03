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
        return call_user_func_array(
            array($this->innerConnection(), 'exec'),
            func_get_args()
        );
    }

    public function prepare($statement, $driverOptions = null)
    {
        return call_user_func_array(
            array($this->innerConnection(), 'prepare'),
            func_get_args()
        );
    }

    public function query($statement)
    {
        return call_user_func_array(
            array($this->innerConnection(), 'query'),
            func_get_args()
        );
    }

    public function quote($string, $parameterType = self::PARAM_STR)
    {
        return call_user_func_array(
            array($this->innerConnection(), 'quote'),
            func_get_args()
        );
    }

    public function lastInsertId($name = NULL)
    {
        return call_user_func_array(
            array($this->innerConnection(), 'lastInsertId'),
            func_get_args()
        );
    }

    public function beginTransaction()
    {
        call_user_func_array(
            array($this->innerConnection(), 'beginTransaction'),
            func_get_args()
        );
    }

    public function commit()
    {
        call_user_func_array(
            array($this->innerConnection(), 'commit'),
            func_get_args()
        );
    }

    public function rollBack()
    {
        call_user_func_array(
            array($this->innerConnection(), 'rollBack'),
            func_get_args()
        );
    }

    public function inTransaction()
    {
        return call_user_func_array(
            array($this->innerConnection(), 'inTransaction'),
            func_get_args()
        );
    }

    public function errorCode()
    {
        return call_user_func_array(
            array($this->innerConnection(), 'errorCode'),
            func_get_args()
        );
    }

    public function errorInfo()
    {
        return call_user_func_array(
            array($this->innerConnection(), 'errorInfo'),
            func_get_args()
        );
    }

    public function getAttribute($attribute)
    {
        return call_user_func_array(
            array($this->innerConnection(), 'getAttribute'),
            func_get_args()
        );
    }

    public function setAttribute($attribute, $value)
    {
        call_user_func_array(
            array($this->innerConnection(), 'setAttribute'),
            func_get_args()
        );
    }

    abstract public function innerConnection();
}
