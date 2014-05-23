<?php
namespace Icecave\Manifold\Authentication\Exception;

use Exception;

/**
 * The credentials file could not be read.
 */
final class CredentialsReadException extends Exception
{
    /**
     * Construct a new credentials read exception.
     *
     * @param string         $path     The credentials path.
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct($path, Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct(
            sprintf(
                'Unable to read Manifold credentials from %s.',
                var_export($path, true)
            ),
            0,
            $previous
        );
    }

    /**
     * Get the credentials path.
     *
     * @return string The credentials path.
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
