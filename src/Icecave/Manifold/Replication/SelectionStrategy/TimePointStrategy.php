<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimePointInterface;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Selects the first connection that is up-to-date
 */
class TimePointStrategy extends AbstractSelectionStrategy
{
    /**
     * Construct a new time point strategy.
     *
     * @param TimePointInterface|integer|null $timePoint The minimum cut-off time for replication delay.
     * @param ClockInterface|null             $clock     The clock to use.
     */
    public function __construct($timePoint = null, ClockInterface $clock = null)
    {
        parent::__construct($clock);

        if (null === $timePoint) {
            $this->timePoint = $this->clock()->localDateTime();
        } else {
            $this->timePoint = $this->normalizeTimePoint($timePoint);
        }
    }

    /**
     * Get the minimum cut-off time for replication delay.
     *
     * @return TimePointInterface The minimum cut-off time for replication delay.
     */
    public function timePoint()
    {
        return $this->timePoint;
    }

    /**
     * Get a single connection from a pool.
     *
     * @param ReplicationManagerInterface $replicationManager The replication manager to use.
     * @param ConnectionPoolInterface     $pool               The pool to select from.
     * @param LoggerInterface|null        $logger             The logger to use.
     *
     * @return ConnectionInterface            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        ReplicationManagerInterface $replicationManager,
        ConnectionPoolInterface $pool,
        LoggerInterface $logger = null
    ) {
        if (null !== $logger) {
            $logger->debug(
                'Selecting connection from pool {pool} with ' .
                    'connection time of at least {timePoint}.',
                array(
                    'pool' => $pool->name(),
                    'timePoint' => $this->timePoint()->isoString(),
                )
            );
        }

        $now = $this->clock()->localDateTime();
        $delayThreshold = $now->differenceAsDuration($this->timePoint());

        if ($delayThreshold->totalSeconds() < 0) {
            if (null !== $logger) {
                $logger->warning(
                    'No acceptable connection found in pool {pool}. ' .
                        'Desired time point {timePoint} is in the future.',
                    array(
                        'pool' => $pool->name(),
                        'timePoint' => $this->timePoint()->isoString(),
                    )
                );
            }

            throw new NoConnectionAvailableException;
        }

        foreach ($pool->connections() as $connection) {
            $delay = $replicationManager->delay($connection, $delayThreshold);

            if (null === $delay) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'not selected from pool {pool}. ' .
                            'The connection is not replicating.',
                        array(
                            'connection' => $connection->name(),
                            'pool' => $pool->name(),
                        )
                    );
                }

                continue;
            }

            $connectionTime = $now->subtract($delay);

            if ($connectionTime->isGreaterThanOrEqualTo($this->timePoint())) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'selected from pool {pool}. ' .
                            'Connection time of {connectionTime} ' .
                            'is at least {timePoint}.',
                        array(
                            'connection' => $connection->name(),
                            'pool' => $pool->name(),
                            'connectionTime' => $connectionTime->isoString(),
                            'timePoint' => $this->timePoint()->isoString(),
                        )
                    );
                }

                return $connection;
            }

            if (null !== $logger) {
                $logger->debug(
                    'Connection {connection} ' .
                        'not selected from pool {pool}. ' .
                        'Connection time is no more than {connectionTime}, ' .
                        'and is less than {timePoint}.',
                    array(
                        'connection' => $connection->name(),
                        'pool' => $pool->name(),
                        'connectionTime' => $connectionTime->isoString(),
                        'timePoint' => $this->timePoint()->isoString(),
                    )
                );
            }
        }

        if (null !== $logger) {
            $logger->warning(
                'No acceptable connection found in pool {pool}. ' .
                    'No connection found with connection time of ' .
                    'at least {timePoint}.',
                array(
                    'pool' => $pool->name(),
                    'timePoint' => $this->timePoint()->isoString(),
                )
            );
        }

        throw new NoConnectionAvailableException;
    }

    private $timePoint;
}
