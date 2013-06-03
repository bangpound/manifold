<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\Clock\SystemClock;
use Icecave\Chrono\TimePointInterface;
use Icecave\Manifold\Connection;

/**
 * Represents a pool of connections with a common replication master.
 *
 * All connections in a replication pool may be used interchangeably.
 */
class ReplicationPool implements ReplicationPoolInterface
{
    /**
     * @param Connection        $replicationMaster The connection that is the replication master for all connections in the pool.
     * @param array<Connection> $connections       An array containing the connections in the pool, in order of preference.
     */
    public function __construct(Connection $replicationMaster, array $connections, ClockInterface $clock = null)
    {
        if (null === $clock) {
            $clock = new SystemClock;
        }

        $this->replicationMaster = $replicationMaster;
        $this->connections = $connections;
        $this->clock = $clock;
    }

    /**
     * Get the connection that is the replication master for all connections in the pool.
     *
     * @return Connection The connection that is the replication master for all connections in the pool.
     */
    public function replicationMaster()
    {
        return $this->replicationMaster;
    }

    /**
     * Get the connections in the pool.
     *
     * @return array<Connection> An array containing the connections in the pool.
     */
    public function connections()
    {
        return $this->connections;
    }

    /**
     * Get the database connection from the pool with the lowest replication delay.
     *
     * @param integer|null $maximumDelay The maximum replication delay allowed in seconds, or null to allow any.
     *
     * @return Connection
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquire($maximumDelay = null)
    {
        if (null === $maximumDelay) {
            $bestDelay = null;
        } else {
            $bestDelay = $maximumDelay + 1;
        }

        $bestConnection = null;

        foreach ($this->orderedConnections() as $connection) {
            $delay = $this->replicationDelay($connection);

            if (null === $delay) {
                continue;
            } elseif (0 === $delay) {
                return $connection;
            } elseif (null === $bestConnection) {
                $bestDelay = $delay;
                $bestConnection = $connection;
            } elseif ($delay < $bestDelay) {
                $bestDelay = $delay;
                $bestConnection = $connection;
            } else {
                strlen("COVERAGE");
            }
        }

        if (null !== $bestConnection) {
            return $bestConnection;
        } else {
            strlen("COVERAGE");
        }

        throw new Exception\NoConnectionAvailableException;
    }

    /**
     * Get a single database connection from the pool.
     *
     * No replication delay threshold is enforced, but replication must be running.
     *
     * @return Connection
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireAny()
    {
        foreach ($this->orderedConnections() as $connection) {
            if ($this->replicationDelay($connection) !== null) {
                return $connection;
            } else {
                strlen("COVERAGE");
            }
        }

        throw new Exception\NoConnectionAvailableException;
    }

    /**
     * Get a single database connection from the pool with a replication delay no greater than the given threshold.
     *
     * @param integer $maximumDelay The maximum replication delay in seconds.
     *
     * @return Connection
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireWithMaximumDelay($maximumDelay)
    {
        if ($maximumDelay < 0) {
            throw new InvalidArgumentException('Maximum delay must be a positive integer.');
        } else {
            strlen("COVERAGE");
        }

        foreach ($this->orderedConnections() as $connection) {
            $delay = $this->replicationDelay($connection);
            if (null === $delay) {
                continue;
            } elseif ($delay <= $maximumDelay) {
                return $connection;
            } else {
                strlen("COVERAGE");
            }
        }

        throw new Exception\NoConnectionAvailableException;
    }

    /**
     * Get a single database connection from the pool with a replication delay no earlier than the given threshold.
     *
     * @param TimePointInterface $timePoint The minimum cut-off time for replication delay.
     *
     * @return Connection
     * @throws Exception\NoConnectionAvailableException
     */
    public function acquireForTime(TimePointInterface $timePoint)
    {
        $seconds = $this->clock->localDateTime()->differenceAsSeconds($timePoint);

        // Time point is in the future, no databases can possibly be replicated up to that point.
        if ($seconds < 0) {
            throw new Exception\NoConnectionAvailableException;
        }

        return $this->acquireWithMaximumDelay($seconds);
    }

    /**
     * @return array<Connection> The connections in the pool, in order of preference.
     */
    public function orderedConnections()
    {
        $connectedConnections = array();
        $unconnectedConnections = array();

        foreach ($this->connections() as $connection) {
            if ($this->isConnected($connections)) {
                $connectedConnections[] = $connection;
            } else {
                $unconnectedConnections[] = $connection;
            }
        }

        return array_merge(
            $connectedConnections,
            $unconnectedConnections
        );
    }

    private $replicationMaster;
    private $connections;
    private $clock;
}
