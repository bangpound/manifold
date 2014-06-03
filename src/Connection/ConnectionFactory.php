<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\Authentication\CredentialsProvider;
use Icecave\Manifold\Authentication\CredentialsProviderInterface;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Creates connections.
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * Construct a new connection factory.
     *
     * @param CredentialsProviderInterface|null  $credentialsProvider  The credentials provider to use.
     * @param array<integer,mixed>|null          $attributes           The connection attributes to use.
     * @param PdoConnectionFactoryInterface|null $pdoConnectionFactory The PDO conneciton factory to use.
     * @param LoggerInterface|null               $logger               The logger to use.
     */
    public function __construct(
        CredentialsProviderInterface $credentialsProvider = null,
        array $attributes = null,
        PdoConnectionFactoryInterface $pdoConnectionFactory = null,
        LoggerInterface $logger = null
    ) {
        if (null === $credentialsProvider) {
            $credentialsProvider = new CredentialsProvider;
        }
        if (null === $attributes) {
            $attributes = array(PDO::ATTR_PERSISTENT => false);
        }
        if (null === $pdoConnectionFactory) {
            $pdoConnectionFactory = new PdoConnectionFactory;
        }

        $this->credentialsProvider = $credentialsProvider;
        $this->attributes = $attributes;
        $this->pdoConnectionFactory = $pdoConnectionFactory;
        $this->logger = $logger;
    }

    /**
     * Get the credentials provider.
     *
     * @return CredentialsProviderInterface The credentials provider.
     */
    public function credentialsProvider()
    {
        return $this->credentialsProvider;
    }

    /**
     * Get the connection attributes.
     *
     * @return array<integer,mixed> The connection attributes.
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Get the PDO connection factory.
     *
     * @return PdoConnectionFactoryInterface The PDO connection factory.
     */
    public function pdoConnectionFactory()
    {
        return $this->pdoConnectionFactory;
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface|null $logger The logger to use, or null to remove the current logger.
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface|null The logger, or null if no logger is in use.
     */
    public function logger()
    {
        return $this->logger;
    }

    /**
     * Create a connection.
     *
     * @param string $name The connection name.
     * @param string $dsn  The data source name.
     *
     * @return ConnectionInterface The newly created connection.
     */
    public function create($name, $dsn)
    {
        return new LazyConnection(
            $name,
            $dsn,
            $this->credentialsProvider(),
            $this->attributes(),
            $this->pdoConnectionFactory(),
            $this->logger()
        );
    }

    private $credentialsProvider;
    private $attributes;
    private $pdoConnectionFactory;
    private $logger;
}
