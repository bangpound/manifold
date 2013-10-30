<?php
namespace Icecave\Manifold\Connection\Facade\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class PdoExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new PdoException('foo', '99999', array('bar', 'baz'), $previous);

        $this->assertSame('foo', $exception->getMessage());
        $this->assertSame('99999', $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(array('bar', 'baz'), $exception->errorInfo);
    }

    public function testExceptionDefaultErrorInfo()
    {
        $previous = new Exception;
        $exception = new PdoException('foo', '99999');

        $this->assertSame('foo', $exception->getMessage());
        $this->assertSame('99999', $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame(array('99999', '99999', 'foo'), $exception->errorInfo);
    }

    public function testExceptionDefaults()
    {
        $exception = new PdoException('foo');

        $this->assertSame('foo', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertNull($exception->errorInfo);
    }
}
