<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The interface implemented by credential providers.
 */
interface CredentialsProviderInterface
{
    /**
     * Get the credentials for the supplied connection.
     *
     * @param ConnectionInterface $connection The connection to provide credentials for.
     *
     * @return CredentialsInterface                    The credentials to connect with.
     * @throws Exception\UndefinedCredentialsException If credentials cannot be determined for the supplied connection.
     */
    public function forConnection(ConnectionInterface $connection);
}
