<?php
namespace Icecave\Manifold\Connection\Container\Exception;

use Exception;
use Phake;
use PHPUnit_Framework_TestCase;

class InvalidDefaultConnectionContainerPairExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $pair = Phake::mock('Icecave\Manifold\Connection\Container\ConnectionContainerPairInterface');
        $previous = new Exception();
        $exception = new InvalidDefaultConnectionContainerPairException($pair, $previous);

        $this->assertSame($pair, $exception->pair());
        $this->assertSame('Invalid default read/write connection container pair supplied.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
