<?php
namespace Icecave\Manifold\Connection;

use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Creates connections.
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * Construct a new connection factory.
     *
     * @param array<integer,mixed>|null $attributes The connection attributes to use.
     * @param LoggerInterface|null      $logger     The logger to use.
     */
    public function __construct(
        array $attributes = null,
        LoggerInterface $logger = null
    ) {
        if (null === $attributes) {
            $attributes = array(
                PDO::ATTR_PERSISTENT => false,
            );
        }
        if (null === $logger) {
            $logger = new NullLogger;
        }

        $this->attributes = $attributes;
        $this->logger = $logger;
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
     * Set the logger.
     *
     * @param LoggerInterface $logger The logger to use.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface The logger.
     */
    public function logger()
    {
        return $this->logger;
    }

    /**
     * Create a connection.
     *
     * @param string          $name     The connection name.
     * @param stringable      $dsn      The data source name.
     * @param stringable|null $username The username.
     * @param stringable|null $password The password.
     *
     * @return ConnectionInterface The newly created connection.
     */
    public function create($name, $dsn, $username = null, $password = null)
    {
        return new LazyConnection(
            $name,
            $dsn,
            $username,
            $password,
            $this->attributes(),
            $this->logger()
        );
    }

    private $attributes;
    private $logger;
}
