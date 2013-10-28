<?php
namespace Icecave\Manifold\Connection\Pool;

use Icecave\Collections\Map;
use Phake;
use PHPUnit_Framework_TestCase;

class ConnectionPoolSelectorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->defaultWrite = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->defaultRead = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->writeA = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->readA = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->writeB = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->readC = Phake::mock(__NAMESPACE__ . '\ConnectionPoolInterface');
        $this->defaults = new ConnectionPoolPair($this->defaultWrite, $this->defaultRead);
        $this->databases = new Map(
            array(
                'databaseA' => new ConnectionPoolPair($this->writeA, $this->readA),
                'databaseB' => new ConnectionPoolPair($this->writeB),
                'databaseC' => new ConnectionPoolPair(null, $this->readC),
                'databaseD' => new ConnectionPoolPair,
            )
        );
        $this->selector = new ConnectionPoolSelector($this->defaults, $this->databases);
    }

    public function testConstructor()
    {
        $this->assertSame($this->defaults, $this->selector->defaults());
        $this->assertSame($this->databases, $this->selector->databases());
    }

    public function testConstructorDefaults()
    {
        $this->selector = new ConnectionPoolSelector($this->defaults);

        $this->assertEquals(new Map, $this->selector->databases());
    }

    public function testConstructorFailureInvalidDefaults()
    {
        $this->defaults = new ConnectionPoolPair;

        $this->setExpectedException(__NAMESPACE__ . '\Exception\InvalidDefaultConnectionPoolPairException');
        new ConnectionPoolSelector($this->defaults);
    }

    public function selectionData()
    {
        //                        name         write           read
        return array(
            'Database A' => array('databaseA', 'writeA',       'readA'),
            'Database B' => array('databaseB', 'writeB',       'defaultRead'),
            'Database C' => array('databaseC', 'defaultWrite', 'readC'),
            'Database D' => array('databaseD', 'defaultWrite', 'defaultRead'),
            'Database E' => array('databaseE', 'defaultWrite', 'defaultRead'),
            'Generic'    => array(null,        'defaultWrite', 'defaultRead'),
        );
    }

    /**
     * @dataProvider selectionData
     */
    public function testForWrite($databaseName, $write, $read)
    {
        $this->assertSame($this->$write, $this->selector->forWrite($databaseName));
    }

    /**
     * @dataProvider selectionData
     */
    public function testForRead($databaseName, $write, $read)
    {
        $this->assertSame($this->$read, $this->selector->forRead($databaseName));
    }

    /**
     * @dataProvider selectionData
     */
    public function testConnectionPoolPair($databaseName, $write, $read)
    {
        $readWritePair = $this->selector->readWritePair($databaseName);

        $this->assertSame($this->$write, $readWritePair->write());
        $this->assertSame($this->$read, $readWritePair->read());
    }
}
