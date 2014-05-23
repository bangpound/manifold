<?php
namespace Icecave\Manifold\Configuration\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class UndefinedConnectionExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new UndefinedConnectionException('foo', $previous);

        $this->assertSame('foo', $exception->name());
        $this->assertSame("Undefined connection or pool 'foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
