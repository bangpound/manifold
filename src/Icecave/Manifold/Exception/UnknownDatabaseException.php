<?php
namespace Icecave\Manifold\Exception;

use Exception;
use Icecave\Manifold\TypeCheck\TypeCheck;
use LogicException;

class UnknownDatabaseException extends LogicException
{
    /**
     * @param Exception|null $previous
     */
    public function __construct(Exception $previous = null)
    {
        TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct('Unknown database.', 0, $previous);
    }
}
