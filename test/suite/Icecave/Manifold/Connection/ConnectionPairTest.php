<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\Connection\LazyPdoConnection;
use PHPUnit_Framework_TestCase;

class ConnectionPairTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->write = new LazyPdoConnection('write');
        $this->read = new LazyPdoConnection('read');
        $this->pair = new ConnectionPair($this->write, $this->read);
    }

    public function testConstructor()
    {
        $this->assertSame($this->write, $this->pair->write());
        $this->assertSame($this->read, $this->pair->read());
    }
}
