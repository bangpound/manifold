<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class UnsupportedQueryExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new UnsupportedQueryException('SELECT * FROM foo', $previous);

        $this->assertSame('SELECT * FROM foo', $exception->query());
        $this->assertSame("Unsupported query 'SELECT * FROM foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
