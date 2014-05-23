<?php
namespace Icecave\Manifold\Connection;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * The interface implemented by connection factories.
 */
interface ConnectionFactoryInterface extends LoggerAwareInterface
{
    /**
     * Get the logger.
     *
     * @return LoggerInterface|null The logger, or null if no logger is in use.
     */
    public function logger();

    /**
     * Create a connection.
     *
     * @param string $name The connection name.
     * @param string $dsn  The data source name.
     *
     * @return ConnectionInterface The newly created connection.
     */
    public function create($name, $dsn);
}
