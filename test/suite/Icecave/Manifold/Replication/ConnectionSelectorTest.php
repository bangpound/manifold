<?php
namespace Icecave\Manifold\Replication;

use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\ConnectionPair;
use Icecave\Manifold\Connection\LazyPdoConnection;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use Icecave\Manifold\Replication\QueryDiscriminator;
use PHPUnit_Framework_TestCase;
use Phake;

class ConnectionSelectorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->poolSelector = Phake::mock('Icecave\Manifold\Connection\Pool\ConnectionPoolSelectorInterface');
        $this->replicationManager = Phake::mock('Icecave\Manifold\Replication\ReplicationManagerInterface');
        $this->defaultStrategy = Phake::mock(__NAMESPACE__ . '\SelectionStrategy\SelectionStrategyInterface');
        $this->queryDiscriminator = new QueryDiscriminator;
        $this->selector = new ConnectionSelector(
            $this->poolSelector,
            $this->replicationManager,
            $this->defaultStrategy,
            $this->queryDiscriminator
        );

        $this->strategy = Phake::mock(__NAMESPACE__ . '\SelectionStrategy\SelectionStrategyInterface');

        $this->connectionA1 = new LazyPdoConnection('A1');
        $this->connectionA2 = new LazyPdoConnection('A2');
        $this->connectionB1 = new LazyPdoConnection('B1');
        $this->connectionB2 = new LazyPdoConnection('B2');
        $this->connectionC1 = new LazyPdoConnection('C1');
        $this->connectionC2 = new LazyPdoConnection('C2');

        $this->poolA = new ConnectionPool(
            new Vector(
                array(
                    $this->connectionA1,
                    $this->connectionA2,
                )
            )
        );
        $this->poolB = new ConnectionPool(
            new Vector(
                array(
                    $this->connectionB1,
                    $this->connectionB2,
                )
            )
        );
        $this->poolC = new ConnectionPool(
            new Vector(
                array(
                    $this->connectionC1,
                    $this->connectionC2,
                )
            )
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->poolSelector, $this->selector->poolSelector());
        $this->assertSame($this->replicationManager, $this->selector->replicationManager());
        $this->assertSame($this->defaultStrategy, $this->selector->defaultStrategy());
        $this->assertSame($this->queryDiscriminator, $this->selector->queryDiscriminator());
    }

    public function testConstructorDefaults()
    {
        $this->selector = new ConnectionSelector($this->poolSelector, $this->replicationManager);

        $this->assertEquals(new SelectionStrategy\AcceptableDelayStrategy, $this->selector->defaultStrategy());
        $this->assertEquals($this->queryDiscriminator, $this->selector->queryDiscriminator());
    }

    public function testSetDefaultStrategy()
    {
        $this->selector->setDefaultStrategy($this->strategy);

        $this->assertSame($this->strategy, $this->selector->defaultStrategy());
    }

    public function testForQuery()
    {
        Phake::when($this->poolSelector)->forRead('foo')->thenReturn($this->poolA);
        Phake::when($this->poolSelector)->forWrite('bar')->thenReturn($this->poolB);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolA)
            ->thenReturn($this->connectionA1);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolB)
            ->thenReturn($this->connectionB1);
        Phake::when($this->strategy)->select($this->replicationManager, $this->poolA)->thenReturn($this->connectionA2);
        Phake::when($this->strategy)->select($this->replicationManager, $this->poolB)->thenReturn($this->connectionB2);

        $this->assertSame($this->connectionA1, $this->selector->forQuery('SELECT * FROM foo.bar'));
        $this->assertSame($this->connectionA2, $this->selector->forQuery('SELECT * FROM foo.bar', $this->strategy));
        $this->assertSame($this->connectionB1, $this->selector->forQuery('DELETE FROM bar.baz'));
        $this->assertSame($this->connectionB2, $this->selector->forQuery('DELETE FROM bar.baz', $this->strategy));
    }

    public function testForWrite()
    {
        Phake::when($this->poolSelector)->forWrite(null)->thenReturn($this->poolA);
        Phake::when($this->poolSelector)->forWrite('foo')->thenReturn($this->poolB);
        Phake::when($this->poolSelector)->forWrite('bar')->thenReturn($this->poolC);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolA)
            ->thenReturn($this->connectionA1);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolB)
            ->thenReturn($this->connectionB1);
        Phake::when($this->strategy)->select($this->replicationManager, $this->poolC)->thenReturn($this->connectionC1);

        $this->assertSame($this->connectionA1, $this->selector->forWrite());
        $this->assertSame($this->connectionB1, $this->selector->forWrite('foo'));
        $this->assertSame($this->connectionC1, $this->selector->forWrite('bar', $this->strategy));
    }

    public function testForRead()
    {
        Phake::when($this->poolSelector)->forRead(null)->thenReturn($this->poolA);
        Phake::when($this->poolSelector)->forRead('foo')->thenReturn($this->poolB);
        Phake::when($this->poolSelector)->forRead('bar')->thenReturn($this->poolC);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolA)
            ->thenReturn($this->connectionA1);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolB)
            ->thenReturn($this->connectionB1);
        Phake::when($this->strategy)->select($this->replicationManager, $this->poolC)->thenReturn($this->connectionC1);

        $this->assertSame($this->connectionA1, $this->selector->forRead());
        $this->assertSame($this->connectionB1, $this->selector->forRead('foo'));
        $this->assertSame($this->connectionC1, $this->selector->forRead('bar', $this->strategy));
    }

    public function testReadWritePair()
    {
        Phake::when($this->poolSelector)->forWrite(null)->thenReturn($this->poolA);
        Phake::when($this->poolSelector)->forWrite('foo')->thenReturn($this->poolB);
        Phake::when($this->poolSelector)->forWrite('bar')->thenReturn($this->poolC);
        Phake::when($this->poolSelector)->forRead(null)->thenReturn($this->poolA);
        Phake::when($this->poolSelector)->forRead('foo')->thenReturn($this->poolB);
        Phake::when($this->poolSelector)->forRead('bar')->thenReturn($this->poolC);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolA)
            ->thenReturn($this->connectionA1)
            ->thenReturn($this->connectionA2);
        Phake::when($this->defaultStrategy)->select($this->replicationManager, $this->poolB)
            ->thenReturn($this->connectionB1)
            ->thenReturn($this->connectionB2);
        Phake::when($this->strategy)->select($this->replicationManager, $this->poolC)
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