<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Connection;

class ConnectionSelectorTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('defaults', 0, 'Icecave\\Manifold\\Connection\\ReadWritePairInterface');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
        if ($argumentCount > 1) {
            $value = $arguments[1];
            $check = function ($value) {
                if (!$value instanceof \Traversable) {
                    return false;
                }
                foreach ($value as $key => $subValue) {
                    if (!\is_string($key)) {
                        return false;
                    }
                    if (!$subValue instanceof \Icecave\Manifold\Connection\ReadWritePairInterface) {
                        return false;
                    }
                }
                return true;
            };
            if (!$check($arguments[1])) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'databases',
                    1,
                    $arguments[1],
                    'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ReadWritePairInterface>'
                );
            }
        }
    }

    public function defaults(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function databases(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function forWrite(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        if ($argumentCount > 0) {
            $value = $arguments[0];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'databaseName',
                    0,
                    $arguments[0],
                    'string|null'
                );
            }
        }
    }

    public function forRead(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        if ($argumentCount > 0) {
            $value = $arguments[0];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'databaseName',
                    0,
                    $arguments[0],
                    'string|null'
                );
            }
        }
    }

    public function readWritePair(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        if ($argumentCount > 0) {
            $value = $arguments[0];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'databaseName',
                    0,
                    $arguments[0],
                    'string|null'
                );
            }
        }
    }

    public function selectReadWritePair(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        if ($argumentCount > 0) {
            $value = $arguments[0];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'databaseName',
                    0,
                    $arguments[0],
                    'string|null'
                );
            }
        }
    }

}
