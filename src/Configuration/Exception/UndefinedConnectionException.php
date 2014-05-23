<?php
namespace Icecave\Manifold\Configuration\Exception;

use Exception;

/**
 * No connection or pool is defined for the supplied connection name.
 */
final class UndefinedConnectionException extends Exception
{
    /**
     * Construct a new undefined connection exception.
     *
     * @param string         $name     The connection name.
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct($name, Exception $previous = null)
    {
        $this->name = $name;

        parent::__construct(
            sprintf(
                'Undefined connection or pool %s.',
                var_export($name, true)
            ),
            0,
            $previous
        );
    }

    /**
     * Get the requested connection name.
     *
     * @return string The connection name.
     */
    public function name()
    {
        return $this->name;
    }

    private $name;
}
