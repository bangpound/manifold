<?php
namespace Icecave\Manifold\Authentication\Caching\Exception;

use Exception;

/**
 * The credentials provider cache file could not be written.
 */
final class CredentialsCacheWriteException extends Exception
{
    /**
     * Construct a new credentials provider cache write exception.
     *
     * @param string         $path     The credentials provider cache path.
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct($path, Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct(
            sprintf(
                'Unable to write Manifold credentials provider cache to %s.',
                var_export($path, true)
            ),
            0,
            $previous
        );
    }

    /**
     * Get the credentials provider cache path.
     *
     * @return string The credentials provider cache path.
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
