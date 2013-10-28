<?php
namespace Icecave\Manifold\Connection\Pool\Exception;

use Exception;
use Phake;
use PHPUnit_Framework_TestCase;

class InvalidDefaultConnectionPoolPairExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $pair = Phake::mock('Icecave\Manifold\Connection\Pool\ConnectionPoolPairInterface');
        $previous = new Exception;
        $exception = new InvalidDefaultConnectionPoolPairException($pair, $previous);

        $this->assertSame($pair, $exception->pair());
        $this->assertSame('Invalid default read/write connection pool pair supplied.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
