<?php
namespace Icecave\Manifold\Authentication\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class UndefinedEnvironmentVariableExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new UndefinedEnvironmentVariableException('foo', $previous);

        $this->assertSame('foo', $exception->name());
        $this->assertSame("Undefined environment variable 'foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
