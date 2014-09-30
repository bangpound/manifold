<?php
namespace Icecave\Manifold\Configuration\Caching\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class ConfigurationCacheWriteExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception();
        $exception = new ConfigurationCacheWriteException('foo', $previous);

        $this->assertSame('foo', $exception->path());
        $this->assertSame("Unable to write Manifold configuration cache to 'foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
