<?php
namespace Icecave\Manifold\Authentication\Exception;

use Exception;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * Credentials could not be determined for the suppled connection.
 */
final class UndefinedCredentialsException extends Exception
{
    /**
     * Construct a new undefined credentials exception.
     *
     * @param ConnectionInterface $connection The connection for which credentials could not be determined.
     * @param Exception|null      $previous   The cause, if available.
     */
    public function __construct(
        ConnectionInterface $connection,
        Exception $previous = null
    ) {
        $this->connection = $connection;

        parent::__construct(
            sprintf(
                'Unable to determine credentials for connection %s.',
                var_export($connection->name(), true)
            ),
            0,
            $previous
        );
    }

    /**
     * Get the connection for which credentials could not be determined.
     *
     * @return ConnectionInterface The connection.
     */
    public function connection()
    {
        return $this->connection;
    }

    private $connection;
}
