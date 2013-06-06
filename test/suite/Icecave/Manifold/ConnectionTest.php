<?php
namespace Icecave\Manifold;

use Phake;
use PHPUnit_Framework_TestCase;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phake::partialMock(__NAMESPACE__ . '\Connection', 'dsn');
    }

    public function testPlaceholder()
    {
        $this->markTestIncomplete();
    }
}
