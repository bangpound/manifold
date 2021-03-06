<?php
namespace Icecave\Manifold\Connection;

use Phake;
use PHPUnit_Framework_TestCase;

class ConnectionPairTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->write = Phake::mock(__NAMESPACE__ . '\ConnectionInterface');
        $this->read = Phake::mock(__NAMESPACE__ . '\ConnectionInterface');
        $this->pair = new ConnectionPair($this->write, $this->read);
    }

    public function testConstructor()
    {
        $this->assertSame($this->write, $this->pair->write());
        $this->assertSame($this->read, $this->pair->read());
    }
}
