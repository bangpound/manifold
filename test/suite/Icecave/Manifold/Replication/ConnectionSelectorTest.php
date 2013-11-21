<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\ConnectionPair;
use Icecave\Manifold\Connection\Container\ConnectionPool;
use PHPUnit_Framework_TestCase;
use Phake;

class ConnectionSelectorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->containerSelector = Phake::mock(
            'Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface'
        );
        $this->replicationManager = Phake::mock('Icecave\Manifold\Replication\ReplicationManagerInterface');
        $this->defaultWriteStrategy = Phake::mock(__NAMESPACE__ . '\SelectionStrategy\SelectionStrategyInterface');
        $this->defaultReadStrategy = Phake::mock(__NAMESPACE__ . '\SelectionStrategy\SelectionStrategyInterface');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->selector = new ConnectionSelector(
            $this->containerSelector,
            $this->replicationManager,
            $this->defaultWriteStrategy,
            $this->defaultReadStrategy,
            $this->logger
        );

        $this->strategy = Phake::mock(__NAMESPACE__ . '\SelectionStrategy\SelectionStrategyInterface');

        $this->connectionA1 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionA1)->name()->thenReturn('A1');
        $this->connectionA2 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionA2)->name()->thenReturn('A2');
        $this->connectionB1 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionB1)->name()->thenReturn('B1');
        $this->connectionB2 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionB2)->name()->thenReturn('B2');
        $this->connectionC1 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionC1)->name()->thenReturn('C1');
        $this->connectionC2 = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionC2)->name()->thenReturn('C2');

        $this->containerA = new ConnectionPool(
            'A',
            array(
                $this->connectionA1,
                $this->connectionA2,
            )
        );
        $this->containerB = new ConnectionPool(
            'B',
            array(
                $this->connectionB1,
                $this->connectionB2,
            )
        );
        $this->containerC = new ConnectionPool(
            'C',
            array(
                $this->connectionC1,
                $this->connectionC2,
            )
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->containerSelector, $this->selector->containerSelector());
        $this->assertSame($this->replicationManager, $this->selector->replicationManager());
        $this->assertSame($this->defaultWriteStrategy, $this->selector->defaultWriteStrategy());
        $this->assertSame($this->defaultReadStrategy, $this->selector->defaultReadStrategy());
        $this->assertSame($this->logger, $this->selector->logger());
    }

    public function testConstructorDefaults()
    {
        $this->selector = new ConnectionSelector($this->containerSelector, $this->replicationManager);

        $this->assertEquals(new SelectionStrategy\AnyStrategy, $this->selector->defaultWriteStrategy());
        $this->assertEquals(new SelectionStrategy\AcceptableDelayStrategy, $this->selector->defaultReadStrategy());
        $this->assertNull($this->selector->logger());
    }

    public function testSetDefaultWriteStrategy()
    {
        $this->selector->setDefaultWriteStrategy($this->strategy);

        $this->assertSame($this->strategy, $this->selector->defaultWriteStrategy());
    }

    public function testSetDefaultReadStrategy()
    {
        $this->selector->setDefaultReadStrategy($this->strategy);

        $this->assertSame($this->strategy, $this->selector->defaultReadStrategy());
    }

    public function testSetLogger()
    {
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->selector->setLogger($this->logger);

        $this->assertSame($this->logger, $this->selector->logger());
    }

    public function testForWrite()
    {
        Phake::when($this->containerSelector)->forWrite(null)->thenReturn($this->containerA);
        Phake::when($this->containerSelector)->forWrite('foo')->thenReturn($this->containerB);
        Phake::when($this->containerSelector)->forWrite('bar')->thenReturn($this->containerC);
        Phake::when($this->defaultWriteStrategy)->select($this->replicationManager, $this->containerA, $this->logger)
            ->thenReturn($this->connectionA1);
        Phake::when($this->defaultWriteStrategy)->select($this->replicationManager, $this->containerB, $this->logger)
            ->thenReturn($this->connectionB1);
        Phake::when($this->strategy)->select($this->replicationManager, $this->containerC, $this->logger)
            ->thenReturn($this->connectionC1);

        $this->assertSame($this->connectionA1, $this->selector->forWrite());
        $this->assertSame($this->connectionB1, $this->selector->forWrite('foo'));
        $this->assertSame($this->connectionC1, $this->selector->forWrite('bar', $this->strategy));
    }

    public function testForRead()
    {
        Phake::when($this->containerSelector)->forRead(null)->thenReturn($this->containerA);
        Phake::when($this->containerSelector)->forRead('foo')->thenReturn($this->containerB);
        Phake::when($this->containerSelector)->forRead('bar')->thenReturn($this->containerC);
        Phake::when($this->defaultReadStrategy)->select($this->replicationManager, $this->containerA, $this->logger)
            ->thenReturn($this->connectionA1);
        Phake::when($this->defaultReadStrategy)->select($this->replicationManager, $this->containerB, $this->logger)
            ->thenReturn($this->connectionB1);
        Phake::when($this->strategy)->select($this->replicationManager, $this->containerC, $this->logger)
            ->thenReturn($this->connectionC1);

        $this->assertSame($this->connectionA1, $this->selector->forRead());
        $this->assertSame($this->connectionB1, $this->selector->forRead('foo'));
        $this->assertSame($this->connectionC1, $this->selector->forRead('bar', $this->strategy));
    }

    public function testReadWritePair()
    {
        Phake::when($this->containerSelector)->forWrite(null)->thenReturn($this->containerA);
        Phake::when($this->containerSelector)->forWrite('foo')->thenReturn($this->containerB);
        Phake::when($this->containerSelector)->forWrite('bar')->thenReturn($this->containerC);
        Phake::when($this->containerSelector)->forRead(null)->thenReturn($this->containerA);
        Phake::when($this->containerSelector)->forRead('foo')->thenReturn($this->containerB);
        Phake::when($this->containerSelector)->forRead('bar')->thenReturn($this->containerC);
        Phake::when($this->defaultWriteStrategy)->select($this->replicationManager, $this->containerA, $this->logger)
            ->thenReturn($this->connectionA1);
        Phake::when($this->defaultReadStrategy)->select($this->replicationManager, $this->containerA, $this->logger)
            ->thenReturn($this->connectionA2);
        Phake::when($this->defaultWriteStrategy)->select($this->replicationManager, $this->containerB, $this->logger)
            ->thenReturn($this->connectionB1);
        Phake::when($this->defaultReadStrategy)->select($this->replicationManager, $this->containerB, $this->logger)
            ->thenReturn($this->connectionB2);
        Phake::when($this->strategy)->select($this->replicationManager, $this->containerC, $this->logger)
            ->thenReturn($this->connectionC1)
            ->thenReturn($this->connectionC2);

        $this->assertEquals(
            new ConnectionPair($this->connectionA1, $this->connectionA2),
            $this->selector->readWritePair()
        );
        $this->assertEquals(
            new ConnectionPair($this->connectionB1, $this->connectionB2),
            $this->selector->readWritePair('foo')
        );
        $this->assertEquals(
            new ConnectionPair($this->connectionC1, $this->connectionC2),
            $this->selector->readWritePair('bar', $this->strategy)
        );
    }
}
