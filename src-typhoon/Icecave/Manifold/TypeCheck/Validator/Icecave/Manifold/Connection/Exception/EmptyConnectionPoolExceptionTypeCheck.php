<?php
namespace Icecave\Manifold\TypeCheck\Validator\Icecave\Manifold\Connection\Exception;

class EmptyConnectionPoolExceptionTypeCheck extends \Icecave\Manifold\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Manifold\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

}
