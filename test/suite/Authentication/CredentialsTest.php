<?php
namespace Icecave\Manifold\Authentication;

use PHPUnit_Framework_TestCase;

class CredentialsTest extends PHPUnit_Framework_TestCase
{
    public function testCredentials()
    {
        $credentials = new Credentials('username', 'password');

        $this->assertSame('username', $credentials->username());
        $this->assertSame('password', $credentials->password());
    }
}
