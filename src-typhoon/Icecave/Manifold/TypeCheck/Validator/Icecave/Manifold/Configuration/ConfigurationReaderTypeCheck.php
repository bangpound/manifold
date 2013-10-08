<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Configuration;

class ConfigurationReaderTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 3) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
    }

    public function reader(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function environmentVariableTransform(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function connectionFactory(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function readFile(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('path', 0, 'string');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'path',
                0,
                $arguments[0],
                'string'
            );
        }
        if ($argumentCount > 1) {
            $value = $arguments[1];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'mimeType',
                    1,
                    $arguments[1],
                    'string|null'
                );
            }
        }
    }

    public function readString(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('data', 0, 'string');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'data',
                0,
                $arguments[0],
                'string'
            );
        }
        if ($argumentCount > 1) {
            $value = $arguments[1];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'mimeType',
                    1,
                    $arguments[1],
                    'string|null'
                );
            }
        }
    }

    public function createConfiguration(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('value', 0, 'Eloquent\\Schemer\\Value\\ObjectValue');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function createConnections(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('value', 0, 'Eloquent\\Schemer\\Value\\ObjectValue');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function createPools(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('value', 0, 'Eloquent\\Schemer\\Value\\ObjectValue');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
    }

    public function createSelector(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 4) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('value', 0, 'Eloquent\\Schemer\\Value\\ObjectValue');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
            }
            if ($argumentCount < 3) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('pools', 2, 'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('defaultConnection', 3, 'PDO');
        } elseif ($argumentCount > 4) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(4, $arguments[4]);
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
        $value = $arguments[2];
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
        if (!$check($arguments[2])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'pools',
                2,
                $arguments[2],
                'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>'
            );
        }
    }

    public function createReplicationTrees(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 4) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('value', 0, 'Eloquent\\Schemer\\Value\\ObjectValue');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
            }
            if ($argumentCount < 3) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('pools', 2, 'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('defaultConnection', 3, 'PDO');
        } elseif ($argumentCount > 4) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(4, $arguments[4]);
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
        $value = $arguments[2];
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
        if (!$check($arguments[2])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'pools',
                2,
                $arguments[2],
                'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>'
            );
        }
    }

    public function createPool(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connectionNames', 0, 'Eloquent\\Schemer\\Value\\ArrayValue');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
    }

    public function createReadWritePair(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 3) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('value', 0, 'Eloquent\\Schemer\\Value\\ObjectValue');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('pools', 2, 'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>');
        } elseif ($argumentCount > 4) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(4, $arguments[4]);
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
        $value = $arguments[2];
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
        if (!$check($arguments[2])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'pools',
                2,
                $arguments[2],
                'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>'
            );
        }
    }

    public function addReplicationNodes(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 5) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('treeNodes', 0, 'Eloquent\\Schemer\\Value\\ValueInterface');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
            }
            if ($argumentCount < 3) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('pools', 2, 'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>');
            }
            if ($argumentCount < 4) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('replicationTree', 3, 'Icecave\\Manifold\\Replication\\ReplicationTreeInterface');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('masterConnection', 4, 'PDO');
        } elseif ($argumentCount > 5) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(5, $arguments[5]);
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
        $value = $arguments[2];
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
        if (!$check($arguments[2])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'pools',
                2,
                $arguments[2],
                'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>'
            );
        }
    }

    public function findConnection(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('name', 0, 'string');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'name',
                0,
                $arguments[0],
                'string'
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
    }

    public function findPool(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 3) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('name', 0, 'string');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 1, 'Icecave\\Collections\\Map<string, PDO>');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('pools', 2, 'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>');
        } elseif ($argumentCount > 3) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'name',
                0,
                $arguments[0],
                'string'
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
                if (!$subValue instanceof \PDO) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'connections',
                1,
                $arguments[1],
                'Icecave\\Collections\\Map<string, PDO>'
            );
        }
        $value = $arguments[2];
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
        if (!$check($arguments[2])) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'pools',
                2,
                $arguments[2],
                'Icecave\\Collections\\Map<string, Icecave\\Manifold\\Connection\\ConnectionPoolInterface>'
            );
        }
    }

    public function createSingleConnectionPool(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

}
