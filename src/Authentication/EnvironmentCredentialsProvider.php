<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Isolator\Isolator;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * A credential provider driven by a predefined set of environment variables.
 */
class EnvironmentCredentialsProvider extends AbstractCredentialsProvider
{
    /**
     * Construct a new credentials provider.
     *
     * @param CredentialsInterface               $defaultCredentials    The default credentials.
     * @param array<string,CredentialsInterface> $connectionCredentials A map of connection name to credentials.
     * @param Isolator|null                      $isolator              The isolator to use.
     */
    public function __construct(
        CredentialsInterface $defaultCredentials = null,
        array $connectionCredentials = null,
        Isolator $isolator = null
    ) {
        parent::__construct($defaultCredentials, $connectionCredentials);

        $this->isolator = Isolator::get($isolator);
        $this->resolvedVariables = array();
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
        $connectionCredentials = $this->connectionCredentials();

        if (array_key_exists($connection->name(), $connectionCredentials)) {
            $credentials = $connectionCredentials[$connection->name()];
        } else {
            $credentials = $this->defaultCredentials();
        }

        try {
            $credentials = new Credentials(
                $this->resolveVariable($credentials->username()),
                $this->resolveVariable($credentials->password())
            );
        } catch (Exception\UndefinedEnvironmentVariableException $e) {
            throw new Exception\UndefinedCredentialsException($connection, $e);
        }

        return $credentials;
    }

    /**
     * Resolve an environment variable name to its current value.
     *
     * @param string|null $variableName The name of the variable to resolve, or null to return null.
     *
     * @return string|null                                     The environment variable value, or null.
     * @throws Exception\UndefinedEnvironmentVariableException If an undefined environment variable is encountered.
     */
    protected function resolveVariable($variableName)
    {
        if (!array_key_exists($variableName, $this->resolvedVariables)) {
            if (null === $variableName) {
                $value = null;
            } else {
                $value = $this->isolator()->getenv($variableName);
                if (false === $value) {
                    throw new Exception\UndefinedEnvironmentVariableException(
                        $variableName
                    );
                }
            }

            $this->resolvedVariables[$variableName] = $value;
        }

        return $this->resolvedVariables[$variableName];
    }

    /**
     * Get the isolator.
     *
     * @return Isolator The isolator.
     */
    protected function isolator()
    {
        return $this->isolator;
    }

    private $isolator;
    private $resolvedVariables;
}
