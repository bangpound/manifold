<?php
namespace Icecave\Manifold;

use Icecave\Manifold\Authentication\CredentialsProviderInterface;
use Icecave\Manifold\Connection\Facade\ConnectionFacadeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * The interface implemented by the primary Manifold factory.
 */
interface ManifoldFactoryInterface extends LoggerAwareInterface
{
    /**
     * Create a Manifold connection facade.
     *
     * @param string|ConfigurationInterface            $configuration  The configuration, or a path to the configuration file.
     * @param string|CredentialsProviderInterface|null $credentials    The credentials provider, or a path to the credentials file, or null to use no credentials.
     * @param string|null                              $connectionName The name of the replication root connection, or null to use the first defined connection.
     * @param array<integer,mixed>|null                $attributes     The connection attributes to use.
     *
     * @return ConnectionFacadeInterface The newly created connection facade.
     */
    public function create(
        $configuration,
        $credentials = null,
        $connectionName = null,
        array $attributes = null
    );

    /**
     * Get the logger.
     *
     * @return LoggerInterface|null The logger, or null if no logger is in use.
     */
    public function logger();
}
