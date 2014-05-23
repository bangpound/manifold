<?php
namespace Icecave\Manifold\Authentication\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class CredentialsReadExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new CredentialsReadException('foo', $previous);

        $this->assertSame('foo', $exception->path());
        $this->assertSame("Unable to read Manifold credentials from 'foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
