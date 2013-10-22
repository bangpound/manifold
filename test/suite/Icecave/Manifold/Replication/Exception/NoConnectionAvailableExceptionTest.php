<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class NoConnectionAvailableExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new NoConnectionAvailableException($previous);

        $this->assertSame('No suitable connection available.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
