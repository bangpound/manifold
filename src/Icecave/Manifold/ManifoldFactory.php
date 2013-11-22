<?php
namespace Icecave\Manifold;

use Icecave\Manifold\Authentication\Caching\CachingCredentialsReader;
use Icecave\Manifold\Authentication\CredentialsProvider;
use Icecave\Manifold\Authentication\CredentialsProviderInterface;
use Icecave\Manifold\Authentication\CredentialsReaderInterface;
use Icecave\Manifold\Configuration\Caching\CachingConfigurationReader;
use Icecave\Manifold\Configuration\ConfigurationInterface;
use Icecave\Manifold\Configuration\ConfigurationReaderInterface;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Connection\ConnectionFactoryInterface;
use Icecave\Manifold\Connection\Facade\ConnectionFacadeInterface;
use Icecave\Manifold\Driver\DriverInterface;
use Icecave\Manifold\Mysql\MysqlDriver;

/**
 * Creates Manifold connection facades from typical inputs.
 */
class ManifoldFactory implements ManifoldFactoryInterface
{
    /**
     * Construct a new Manifold factory.
     *
     * @param DriverInterface|null              $driver              The driver to use.
     * @param ConfigurationReaderInterface|null $configurationReader The configuration reader to use.
     * @param CredentialsReaderInterface|null   $credentialsReader   The credentials reader to use.
     */
    public function __construct(
        DriverInterface $driver = null,
        ConfigurationReaderInterface $configurationReader = null,
        CredentialsReaderInterface $credentialsReader = null
    ) {
        if (null === $driver) {
            $driver = new MysqlDriver;
        }
        if (null === $configurationReader) {
            $configurationReader = new CachingConfigurationReader;
        }
        if (null === $credentialsReader) {
            $credentialsReader = new CachingCredentialsReader;
        }

        $this->driver = $driver;
        $this->configurationReader = $configurationReader;
        $this->credentialsReader = $credentialsReader;
    }

    /**
     * Get the driver.
     *
     * @return DriverInterface The driver.
     */
    public function driver()
    {
        return $this->driver;
    }

    /**
     * Get the configuration reader.
     *
     * @return ConfigurationReaderInterface The configuration reader.
     */
    public function configurationReader()
    {
        return $this->configurationReader;
    }

    /**
     * Get the credentials reader.
     *
     * @return CredentialsReaderInterface The credentials reader.
     */
    public function credentialsReader()
    {
        return $this->credentialsReader;
    }

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
    ) {
        $configuration = $this->adaptConfiguration(
            $configuration,
            $this->adaptCredentials($credentials)
        );

        if (null === $connectionName) {
            return $this->driver()->createFirstConnection(
                $configuration,
                $attributes
            );
        }

        return $this->driver()->createConnectionByName(
            $configuration,
            $connectionName,
            $attributes
        );
    }

    /**
     * Adapt the supplied credentials into a concrete instance of
     * CredentialsProviderInterface.
     *
     * @param string|CredentialsProviderInterface|null $credentials The credentials provider, or a path to the credentials file, or null to use no credentials.
     *
     * @return CredentialsProviderInterface The concrete credentials provider instance.
     */
    protected function adaptCredentials($credentials)
    {
        if (null === $credentials) {
            return new CredentialsProvider;
        }

        if ($credentials instanceof CredentialsProviderInterface) {
            return $credentials;
        }

        return $this->credentialsReader()->readFile($credentials);
    }

    /**
     * Adapt the supplied configuration into a concrete instance of
     * ConfigurationInterface.
     *
     * @param string|ConfigurationInterface $configuration       The configuration, or a path to the configuration file.
     * @param CredentialsProviderInterface  $credentialsProvider The credentials provider to use.
     *
     * @return ConfigurationInterface The concrete configuration instance.
     */
    protected function adaptConfiguration(
        $configuration,
        CredentialsProviderInterface $credentialsProvider
    ) {
        if ($configuration instanceof ConfigurationInterface) {
            return $configuration;
        }

        return $this->configurationReader()->readFile(
            $configuration,
            null,
            $this->createConnectionFactory($credentialsProvider)
        );
    }

    /**
     * Create a connection factory for the supplied credentials provider.
     *
     * @param CredentialsProviderInterface $credentialsProvider The credentials provider to use.
     *
     * @return ConnectionFactoryInterface The newly created connection factory.
     */
    protected function createConnectionFactory(
        CredentialsProviderInterface $credentialsProvider
    ) {
        return new ConnectionFactory($credentialsProvider);
    }

    private $driver;
    private $configurationReader;
    private $credentialsReader;
}
