<?php
namespace Icecave\Manifold\Authentication;

/**
 * An abstract base class for implementing credential providers.
 */
abstract class AbstractCredentialsProvider implements
    CredentialsProviderInterface
{
    /**
     * Construct a new credentials provider.
     *
     * @param CredentialsInterface               $defaultCredentials    The default credentials.
     * @param array<string,CredentialsInterface> $connectionCredentials A map of connection name to credentials.
     */
    public function __construct(
        CredentialsInterface $defaultCredentials = null,
        array $connectionCredentials = null
    ) {
        if (null === $defaultCredentials) {
            $defaultCredentials = new Credentials;
        }
        if (null === $connectionCredentials) {
            $connectionCredentials = array();
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
     * @return array<string,CredentialsInterface> The connection credential map.
     */
    public function connectionCredentials()
    {
        return $this->connectionCredentials;
    }

    private $defaultCredentials;
    private $connectionCredentials;
}
