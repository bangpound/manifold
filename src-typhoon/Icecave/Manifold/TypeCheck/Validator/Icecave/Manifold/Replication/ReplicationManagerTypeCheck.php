<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Replication;

class ReplicationManagerTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('replicationTree', 0, 'Icecave\\Manifold\\Replication\\ReplicationTree');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function replicationTree(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function replicationDelay(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('masterConnection', 0, 'PDO');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('slaveConnection', 1, 'PDO');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

    public function replicationDelayWithin(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 3) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('threshold', 0, 'integer');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('masterConnection', 1, 'PDO');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('slaveConnection', 2, 'PDO');
        } elseif ($argumentCount > 3) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
        $value = $arguments[0];
        if (!\is_int($value)) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                'threshold',
                0,
                $arguments[0],
                'integer'
            );
        }
    }

    public function isReplicating(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('masterConnection', 0, 'PDO');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('slaveConnection', 1, 'PDO');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

    public function waitForReplication(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('masterConnection', 0, 'PDO');
            }
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('slaveConnection', 1, 'PDO');
        } elseif ($argumentCount > 3) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
        if ($argumentCount > 2) {
            $value = $arguments[2];
            if (!(\is_int($value) || $value === null)) {
                throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'timeout',
                    2,
                    $arguments[2],
                    'integer|null'
                );
            }
        }
    }

    public function secondsBehindMaster(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

}
