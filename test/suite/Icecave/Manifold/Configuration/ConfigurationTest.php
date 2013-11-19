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
        $this->connectionContainerSelector = Phake::mock(
            'Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface'
        );
        $this->replicationTrees = new Vector;
        $this->configuration = new Configuration(
            $this->connections,
            $this->connectionPools,
            $this->connectionContainerSelector,
            $this->replicationTrees
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->connections, $this->configuration->connections());
        $this->assertSame($this->connectionPools, $this->configuration->connectionPools());
        $this->assertSame($this->connectionContainerSelector, $this->configuration->connectionContainerSelector());
        $this->assertSame($this->replicationTrees, $this->configuration->replicationTrees());
    }
}
