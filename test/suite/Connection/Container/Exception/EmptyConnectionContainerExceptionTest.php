<?php
namespace Icecave\Manifold\Connection\Container\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class EmptyConnectionContainerExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new EmptyConnectionContainerException($previous);

        $this->assertSame('Connection containers cannot be empty.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
