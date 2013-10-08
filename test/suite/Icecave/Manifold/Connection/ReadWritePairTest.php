<?php
namespace Icecave\Manifold\Connection;

use Phake;
use PHPUnit_Framework_TestCase;

class ReadWritePairTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->write = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->read = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->pair = new ReadWritePair($this->write, $this->read);
    }

    public function testConstructor()
    {
        $this->assertSame($this->write, $this->pair->write());
        $this->assertSame($this->read, $this->pair->read());
    }

    public function testConstructorDefaults()
    {
        $this->pair = new ReadWritePair;

        $this->assertNull($this->pair->write());
        $this->assertNull($this->pair->read());
    }
}
