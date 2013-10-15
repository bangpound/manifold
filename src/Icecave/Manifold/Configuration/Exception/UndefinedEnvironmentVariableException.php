<?php
namespace Icecave\Manifold\Configuration\Exception;

use Exception;

/**
 * An undefined environment variable was encountered in configuration data.
 */
final class UndefinedEnvironmentVariableException extends Exception
{
    /**
     * Construct a new undefined environment variable exception.
     *
     * @param string         $name     The variable name.
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct($name, Exception $previous = null)
    {
        $this->name = $name;

        parent::__construct(
            sprintf(
                'Undefined environment variable %s.',
                var_export($name, true)
            ),
            0,
            $previous
        );
    }

    /**
     * Get the requested variable name.
     *
     * @return string The variable name.
     */
    public function name()
    {
        return $this->name;
    }

    private $name;
}
