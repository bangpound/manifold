<?php
namespace Icecave\Manifold\Authentication\Exception;

use Exception;
use Phake;
use PHPUnit_Framework_TestCase;

class UndefinedCredentialsExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $connection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($connection)->name()->thenReturn('foo');
        $previous = new Exception();
        $exception = new UndefinedCredentialsException($connection, $previous);

        $this->assertSame($connection, $exception->connection());
        $this->assertSame("Unable to determine credentials for connection 'foo'.", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
