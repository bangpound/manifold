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
     * @param string          $name     The connection name.
     * @param stringable      $dsn      The data source name.
     * @param stringable|null $username The username.
     * @param stringable|null $password The password.
     *
     * @return PDO The newly created connection.
     */
    public function create($name, $dsn, $username = null, $password = null);
}
