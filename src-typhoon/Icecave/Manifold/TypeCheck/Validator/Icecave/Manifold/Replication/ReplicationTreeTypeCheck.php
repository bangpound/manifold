<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Replication;

class ReplicationTreeTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('replicationRoot', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function replicationRoot(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function hasConnection(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function isRoot(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function isLeaf(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function isMaster(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function isSlave(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function masterOf(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function slavesOf(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function isReplicatingTo(array $arguments)
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

    public function isMasterOf(array $arguments)
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

    public function countHops(array $arguments)
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

    public function replicationPath(array $arguments)
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

    public function addSlave(array $arguments)
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

    public function removeSlave(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function createEntry(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function getEntry(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connection', 0, 'PDO');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

}
