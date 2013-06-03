<?php
namespace Icecave\Manifold\Proxy;

use PDO;
use Phake;
use PHPUnit_Framework_TestCase;

class ProxyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phake::mock('PDO');
        $this->proxy = new Proxy($this->connection);
    }

    public function testInnerConnection()
    {
        $this->assertSame($this->connection, $this->proxy->innerConnection());
    }
}
