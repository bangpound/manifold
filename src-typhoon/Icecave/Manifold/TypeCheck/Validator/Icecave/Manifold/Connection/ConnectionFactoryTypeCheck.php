<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Connection;

class ConnectionFactoryTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function driverOptions(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function createConnection(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('dsn', 0, 'string');
        } elseif ($argumentCount > 3) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'dsn',
                0,
                $arguments[0],
                'string'
            );
        }
        if ($argumentCount > 1) {
            $value = $arguments[1];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'username',
                    1,
                    $arguments[1],
                    'string|null'
                );
            }
        }
        if ($argumentCount > 2) {
            $value = $arguments[2];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'password',
                    2,
                    $arguments[2],
                    'string|null'
                );
            }
        }
    }

}
