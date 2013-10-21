<?php
namespace Icecave\Manifold\Connection\Pool\Exception;

use Exception;

/**
 * The supplied default read/write pair is invalid.
 */
final class InvalidDefaultReadWritePairException extends Exception
{
    /**
     * Construct a new invalid default read/write pair exception.
     *
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct(Exception $previous = null)
    {
        parent::__construct(
            'Invalid default read/write pair supplied.',
            0,
            $previous
        );
    }
}
