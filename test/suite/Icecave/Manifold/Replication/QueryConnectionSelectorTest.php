<?php
namespace Icecave\Manifold\Replication;

use PHPUnit_Framework_TestCase;
use Phake;

class QueryConnectionSelectorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->innerSelector = Phake::mock(__NAMESPACE__ . '\ConnectionSelectorInterface');
        $this->queryDiscriminator = new QueryDiscriminator;
        $this->selector = new QueryConnectionSelector($this->innerSelector, $this->queryDiscriminator);

        $this->strategy = Phake::mock(__NAMESPACE__ . '\SelectionStrategy\SelectionStrategyInterface');

        $this->connectionA = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connectionA->id = 'A';
        $this->connectionB = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connectionB->id = 'B';
        $this->connectionC = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connectionC->id = 'C';
        $this->connectionD = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        $this->connectionD->id = 'D';
    }

    public function testConstructor()
    {
        $this->assertSame($this->innerSelector, $this->selector->selector());
        $this->assertSame($this->queryDiscriminator, $this->selector->queryDiscriminator());
    }

    public function testConstructorDefaults()
    {
        $this->selector = new QueryConnectionSelector($this->innerSelector);

        $this->assertEquals($this->queryDiscriminator, $this->selector->queryDiscriminator());
    }

    public function testSelect()
    {
        Phake::when($this->innerSelector)->forRead('foo', null)->thenReturn($this->connectionA);
        Phake::when($this->innerSelector)->forRead('foo', $this->strategy)->thenReturn($this->connectionB);
        Phake::when($this->innerSelector)->forWrite('bar', null)->thenReturn($this->connectionC);
        Phake::when($this->innerSelector)->forWrite('bar', $this->strategy)->thenReturn($this->connectionD);

        $this->assertSame(array($this->connectionA, false), $this->selector->select('SELECT * FROM foo.bar'));
        $this->assertSame(array($this->connectionB, false), $this->selector->select('SELECT * FROM foo.bar', $this->strategy));
        $this->assertSame(array($this->connectionC, true), $this->selector->select('DELETE FROM bar.baz'));
        $this->assertSame(array($this->connectionD, true), $this->selector->select('DELETE FROM bar.baz', $this->strategy));
    }
}
