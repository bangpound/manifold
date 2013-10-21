<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use PHPUnit_Framework_TestCase;
use Phake;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->connections = new Map;
        $this->connectionPools = new Map;
        $this->connectionPoolSelector = Phake::mock('Icecave\Manifold\Connection\Pool\ConnectionPoolSelectorInterface');
        $this->replicationTrees = new Vector;
        $this->configuration = new Configuration(
            $this->connections,
            $this->connectionPools,
            $this->connectionPoolSelector,
            $this->replicationTrees
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->connections, $this->configuration->connections());
        $this->assertSame($this->connectionPools, $this->configuration->connectionPools());
        $this->assertSame($this->connectionPoolSelector, $this->configuration->connectionPoolSelector());
        $this->assertSame($this->replicationTrees, $this->configuration->replicationTrees());
    }
}
