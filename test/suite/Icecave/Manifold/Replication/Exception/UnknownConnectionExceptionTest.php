<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use Phake;
use PHPUnit_Framework_TestCase;

class UnknownConnectionExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $connection = Phake::mock('PDO');
        $previous = new Exception;
        $exception = new UnknownConnectionException($connection, $previous);

        $this->assertSame($connection, $exception->connection());
        $this->assertSame('Unknown connection.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
