<?php
namespace Icecave\Manifold;

use Icecave\Manifold\Replication\Exception\ReplicationException;
use Icecave\Manifold\Proxy\LazyConnectProxy;

class Connection extends LazyConnectProxy
{
    public function isReplicating()
    {
        $status = $this->replicationStatus(false);

        if (null === $status) {
            return false;
        }

        return 'Yes' === $status->Slave_SQL_Running;
    }

    public function replicationDelay()
    {
        $status = $this->replicationStatus();

        if ('Yes' === $status->Slave_SQL_Running) {
            return intval($status->Seconds_Behind_Master);
        }

        if ($status->Last_Error) {
            throw new ReplicationException(
                'Replication has stopped: ' . $status->Last_Error,
                $status->Last_Errno
            );
        }

        throw new ReplicationException('Replication has stopped.', $code);
    }

    private function replicationStatus($strict = true)
    {
        $result = $this->query('SHOW SLAVE STATUS')->fetchObject();

        if (is_object($result)) {
            return $result;
        }

        if ($strict) {
            throw new ReplicationException('Could not query replication status.');
        }

        return null;
    }
}
