<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Configuration;

class ConfigurationTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 4) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 0, 'Icecave\\Collections\\Map<string, PDO>');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connectionPools', 1, 'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>');
            }
            if ($argumentCount < 3) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connectionSelector', 2, 'Icecave\\Manifold\\Connection\\ConnectionSelectorInterface');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('replicationTrees', 3, 'Icecave\\Collections\\Vector<Icecave\\Manifold\\Replication\\ReplicationTreeInterface>');
        } elseif ($argumentCount > 4) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(4, $arguments[4]);
        }
        $value = $arguments[0];
        $check = function ($value) {
            if (!$value instanceof \Traversable) {
                return false;
            }
            foreach ($value as $key => $subValue) {
                if (!\is_string($key)) {
                    return false;
                }
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[0])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                0,
                $arguments[0],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
        $value = $arguments[1];
        $check = function ($value) {
            if (!$value instanceof \Traversable) {
                return false;
            }
            foreach ($value as $key => $subValue) {
                if (!\is_string($key)) {
                    return false;
                }
                if (!$subValue instanceof \Icecave\Manifold\Connection\ConnectionPoolInterface) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connectionPools',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>'
            );
        }
        $value = $arguments[3];
        $check = function ($value) {
            if (!$value instanceof \Traversable) {
                return false;
            }
            foreach ($value as $key => $subValue) {
                if (!$subValue instanceof \Icecave\Manifold\Replication\ReplicationTreeInterface) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[3])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'replicationTrees',
                3,
                $arguments[3],
                'Icecave\\Collections\\Vector<Icecave\\Manifold\\Replication\\ReplicationTreeInterface>'
            );
        }
    }

    public function connections(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function connectionPools(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function connectionSelector(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function replicationTrees(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

}
