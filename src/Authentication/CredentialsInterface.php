<?php
namespace Icecave\Manifold\Authentication;

/**
 * The interface implemented by credentials.
 */
interface CredentialsInterface
{
    /**
     * Get the username.
     *
     * @return string|null The username.
     */
    public function username();

    /**
     * Get the password.
     *
     * @return string|null The password.
     */
    public function password();
}
