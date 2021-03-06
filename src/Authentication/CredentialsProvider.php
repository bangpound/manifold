<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * A credential provider driven by a predefined set of string values.
 */
class CredentialsProvider extends AbstractCredentialsProvider implements
    StaticCredentialsProviderInterface
{
    /**
     * Get the credentials for the supplied connection.
     *
     * @param ConnectionInterface $connection The connection to provide credentials for.
     *
     * @return CredentialsInterface                    The credentials to connect with.
     * @throws Exception\UndefinedCredentialsException If credentials cannot be determined for the supplied connection.
     */
    public function forConnection(ConnectionInterface $connection)
    {
        $connectionCredentials = $this->connectionCredentials();

        if (array_key_exists($connection->name(), $connectionCredentials)) {
            return $connectionCredentials[$connection->name()];
        }

        return $this->defaultCredentials();
    }
}
