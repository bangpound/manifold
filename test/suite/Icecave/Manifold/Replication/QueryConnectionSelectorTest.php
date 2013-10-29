<?php
namespace Icecave\Manifold\Replication;

use Icecave\Manifold\Connection\LazyPdoConnection;
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

        $this->connectionA = new LazyPdoConnection('A');
        $this->connectionB = new LazyPdoConnection('B');
        $this->connectionC = new LazyPdoConnection('C');
        $this->connectionD = new LazyPdoConnection('D');
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

        $this->assertSame($this->connectionA, $this->selector->select('SELECT * FROM foo.bar'));
        $this->assertSame($this->connectionB, $this->selector->select('SELECT * FROM foo.bar', $this->strategy));
        $this->assertSame($this->connectionC, $this->selector->select('DELETE FROM bar.baz'));
        $this->assertSame($this->connectionD, $this->selector->select('DELETE FROM bar.baz', $this->strategy));
    }
}
