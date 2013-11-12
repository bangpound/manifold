<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Collections\Map;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * A credential provider driven by a predefined set of string values.
 */
class CredentialsProvider implements CredentialsProviderInterface
{
    /**
     * Construct a new string credentials provider.
     *
     * @param CredentialsInterface             $defaultCredentials    The default credentials to return.
     * @param Map<string,CredentialsInterface> $connectionCredentials A map of connection name to credentials.
     */
    public function __construct(
        CredentialsInterface $defaultCredentials = null,
        Map $connectionCredentials = null
    ) {
        if (null === $defaultCredentials) {
            $defaultCredentials = new Credentials;
        }
        if (null === $connectionCredentials) {
            $connectionCredentials = new Map;
        }

        $this->defaultCredentials = $defaultCredentials;
        $this->connectionCredentials = $connectionCredentials;
    }

    /**
     * Get the default credentials.
     *
     * @return CredentialsInterface The default credentials.
     */
    public function defaultCredentials()
    {
        return $this->defaultCredentials;
    }

    /**
     * Get the connection credential map.
     *
     * @return Map<string,CredentialsInterface> The connection credential map.
     */
    public function connectionCredentials()
    {
        return $this->connectionCredentials;
    }

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
        if (
            $this->connectionCredentials()
                ->tryGet($connection->name(), $credentials)
        ) {
            return $credentials;
        }

        return $this->defaultCredentials();
    }

    private $defaultCredentials;
    private $connectionCredentials;
}
