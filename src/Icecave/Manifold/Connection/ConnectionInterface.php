<?php
namespace Icecave\Manifold\Connection;

/**
 * The interface implemented by concrete connections.
 *
 * This interface extends the PDO connection interface to add information
 * necessary to serialize the connection for later use.
 */
interface ConnectionInterface extends PdoConnectionInterface
{
    /**
     * Get the connection name.
     *
     * @return string The connection name.
     */
    public function name();

    /**
     * Get the data source name.
     *
     * @return stringable The data source name.
     */
    public function dsn();

    /**
     * Get the username.
     *
     * @return stringable|null The username, or null if no username is in use.
     */
    public function username();

    /**
     * Get the password.
     *
     * @return stringable|null The password, or null if no password is in use.
     */
    public function password();

    /**
     * Get the connection attributes.
     *
     * @return array<integer,mixed> The connection attributes.
     */
    public function attributes();
}
