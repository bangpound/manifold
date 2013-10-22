<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;

/**
 * No suitable connection was found for selection.
 */
class NoConnectionAvailableException extends Exception
{
    /**
     * Construct a new no connection available exception.
     *
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct(Exception $previous = null)
    {
        parent::__construct('No suitable connection available.', 0, $previous);
    }
}
