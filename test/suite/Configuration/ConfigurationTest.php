<?php
namespace Icecave\Manifold\Configuration;

use PHPUnit_Framework_TestCase;
use Phake;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->connections = array(
            Phake::mock('Icecave\Manifold\Connection\ConnectionInterface'),
            Phake::mock('Icecave\Manifold\Connection\ConnectionInterface'),
        );
        $this->connectionPools = array(
            Phake::mock('Icecave\Manifold\Connection\Container\ConnectionPoolInterface'),
            Phake::mock('Icecave\Manifold\Connection\Container\ConnectionPoolInterface'),
        );
        $this->connectionContainerSelector = Phake::mock(
            'Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface'
        );
        $this->replicationTrees = array(
            Phake::mock('Icecave\Manifold\Replication\ReplicationTree'),
            Phake::mock('Icecave\Manifold\Replication\ReplicationTree'),
        );
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
