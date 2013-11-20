<?php
namespace Icecave\Manifold\Configuration\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class ConfigurationReadExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new ConfigurationReadException('foo', $previous);

        $this->assertSame('foo', $exception->path());
        $this->assertSame("Unable to read Manifold configuration from 'foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
