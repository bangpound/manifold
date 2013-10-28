<?php
namespace Icecave\Manifold\Connection\Pool;

use Phake;
use PHPUnit_Framework_TestCase;

class ConnectionPoolPairTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->write = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->read = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->pair = new ConnectionPoolPair($this->write, $this->read);
    }

    public function testConstructor()
    {
        $this->assertSame($this->write, $this->pair->write());
        $this->assertSame($this->read, $this->pair->read());
    }

    public function testConstructorDefaults()
    {
        $this->pair = new ConnectionPoolPair;

        $this->assertNull($this->pair->write());
        $this->assertNull($this->pair->read());
    }
}
