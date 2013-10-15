<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Connection;

class ConnectionPoolTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\MissingArgumentException('connections', 0, 'Icecave\\Collections\\Vector<Icecave\\Manifold\\Connection\\PDO>');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        $check = function ($value) {
            if (!$value instanceof \Traversable) {
                return false;
            }
            foreach ($value as $key => $subValue) {
                if (!$subValue instanceof \Icecave\Manifold\Connection\PDO) {
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
                'Icecave\\Collections\\Vector<Icecave\\Manifold\\Connection\\PDO>'
            );
        }
    }

    public function connections(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

}
