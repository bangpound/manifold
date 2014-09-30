<?php
namespace Icecave\Manifold\Connection\Container;

use Phake;
use PHPUnit_Framework_TestCase;

class ConnectionContainerPairTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->write = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->read = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->pair = new ConnectionContainerPair($this->write, $this->read);
    }

    public function testConstructor()
    {
        $this->assertSame($this->write, $this->pair->write());
        $this->assertSame($this->read, $this->pair->read());
    }

    public function testConstructorDefaults()
    {
        $this->pair = new ConnectionContainerPair();

        $this->assertNull($this->pair->write());
        $this->assertNull($this->pair->read());
    }
}
