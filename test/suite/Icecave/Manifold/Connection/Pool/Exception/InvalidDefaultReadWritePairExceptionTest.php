<?php
namespace Icecave\Manifold\Connection\Pool\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class InvalidDefaultReadWritePairExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new InvalidDefaultReadWritePairException($previous);

        $this->assertSame('Invalid default read/write pair supplied.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
