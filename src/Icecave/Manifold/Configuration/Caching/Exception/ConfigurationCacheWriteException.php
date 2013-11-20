<?php
namespace Icecave\Manifold\Configuration\Caching\Exception;

use Exception;

/**
 * The configuration cache file could not be written.
 */
final class ConfigurationCacheWriteException extends Exception
{
    /**
     * Construct a new configuration cache write exception.
     *
     * @param string         $path     The configuration cache path.
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct($path, Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct(
            sprintf(
                'Unable to write Manifold configuration cache to %s.',
                var_export($path, true)
            ),
            0,
            $previous
        );
    }

    /**
     * Get the configuration cache path.
     *
     * @return string The configuration cache path.
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
