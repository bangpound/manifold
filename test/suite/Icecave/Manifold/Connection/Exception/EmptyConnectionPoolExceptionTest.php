<?php
namespace Icecave\Manifold\Connection\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class EmptyConnectionPoolExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new EmptyConnectionPoolException($previous);

        $this->assertSame('Connection pools cannot be empty.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
