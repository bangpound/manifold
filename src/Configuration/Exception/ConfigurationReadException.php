<?php
namespace Icecave\Manifold\Configuration\Exception;

use Exception;

/**
 * The configuration file could not be read.
 */
final class ConfigurationReadException extends Exception
{
    /**
     * Construct a new configuration read exception.
     *
     * @param string         $path     The configuration path.
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct($path, Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct(
            sprintf(
                'Unable to read Manifold configuration from %s.',
                var_export($path, true)
            ),
            0,
            $previous
        );
    }

    /**
     * Get the configuration path.
     *
     * @return string The configuration path.
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
