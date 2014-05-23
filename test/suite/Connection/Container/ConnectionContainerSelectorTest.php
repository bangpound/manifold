<?php
namespace Icecave\Manifold\Connection\Container;

use Phake;
use PHPUnit_Framework_TestCase;

class ConnectionContainerSelectorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->defaultWrite = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->defaultRead = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->writeA = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->readA = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->writeB = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->readC = Phake::mock(__NAMESPACE__ . '\ConnectionContainerInterface');
        $this->defaults = new ConnectionContainerPair($this->defaultWrite, $this->defaultRead);
        $this->databases = array(
            'databaseA' => new ConnectionContainerPair($this->writeA, $this->readA),
            'databaseB' => new ConnectionContainerPair($this->writeB),
            'databaseC' => new ConnectionContainerPair(null, $this->readC),
            'databaseD' => new ConnectionContainerPair,
        );
        $this->selector = new ConnectionContainerSelector($this->defaults, $this->databases);
    }

    public function testConstructor()
    {
        $this->assertSame($this->defaults, $this->selector->defaults());
        $this->assertSame($this->databases, $this->selector->databases());
    }

    public function testConstructorDefaults()
    {
        $this->selector = new ConnectionContainerSelector($this->defaults);

        $this->assertSame(array(), $this->selector->databases());
    }

    public function testConstructorFailureInvalidDefaults()
    {
        $this->defaults = new ConnectionContainerPair;

        $this->setExpectedException(__NAMESPACE__ . '\Exception\InvalidDefaultConnectionContainerPairException');
        new ConnectionContainerSelector($this->defaults);
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
    public function testConnectionContainerPair($databaseName, $write, $read)
    {
        $readWritePair = $this->selector->readWritePair($databaseName);

        $this->assertSame($this->$write, $readWritePair->write());
        $this->assertSame($this->$read, $readWritePair->read());
    }
}
