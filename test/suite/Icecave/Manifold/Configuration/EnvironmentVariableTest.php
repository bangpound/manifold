<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

class EnvironmentVariableTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock(Isolator::className());
        $this->variable = new EnvironmentVariable('foo', $this->isolator);
    }

    public function testConstructor()
    {
        $this->assertSame('foo', $this->variable->name());
    }

    public function testValue()
    {
        Phake::when($this->isolator)->getenv('foo')->thenReturn('bar');

        $this->assertSame('bar', $this->variable->value());
        $this->assertSame('bar', $this->variable->value());
        Phake::verify($this->isolator)->getenv('foo');
    }

    public function testValueFailure()
    {
        Phake::when($this->isolator)->getenv('foo')->thenReturn(false);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\UndefinedEnvironmentVariableException');
        $this->variable->value();
    }

    public function testToString()
    {
        Phake::when($this->isolator)->getenv('foo')->thenReturn('bar');

        $this->assertSame('bar', strval($this->variable));
        $this->assertSame('bar', strval($this->variable));
        Phake::verify($this->isolator)->getenv('foo');
    }

    public function testToStringFailure()
    {
        Phake::when($this->isolator)->getenv('foo')->thenReturn(false);

        $this->assertSame('', strval($this->variable));
    }
}
