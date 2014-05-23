<?php
namespace Icecave\Manifold\Authentication;

/**
 * Represents a set of database credentials.
 */
class Credentials implements CredentialsInterface
{
    /**
     * Construct a new credentials instance.
     *
     * @param string|null $username The username.
     * @param string|null $password The password.
     */
    public function __construct($username = null, $password = null)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get the username.
     *
     * @return string|null The username.
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * Get the password.
     *
     * @return string|null The password.
     */
    public function password()
    {
        return $this->password;
    }

    private $username;
    private $password;
}
