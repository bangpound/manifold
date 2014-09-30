<?php
namespace Icecave\Manifold\Authentication\Caching\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class CredentialsCacheWriteExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception();
        $exception = new CredentialsCacheWriteException('foo', $previous);

        $this->assertSame('foo', $exception->path());
        $this->assertSame("Unable to write Manifold credentials provider cache to 'foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
