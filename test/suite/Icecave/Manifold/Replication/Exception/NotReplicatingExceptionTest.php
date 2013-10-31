<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use Phake;
use PHPUnit_Framework_TestCase;

class NotReplicatingExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $connection = Phake::mock('PDO');
        $previous = new Exception;
        $exception = new NotReplicatingException($connection, $previous);

        $this->assertSame($connection, $exception->connection());
        $this->assertSame('The connection is not currently replicating.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
