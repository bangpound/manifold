<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Selects the first connection with a replication delay less than the specified
 * maximum delay.
 */
class AcceptableDelayStrategy extends AbstractSelectionStrategy
{
    /**
     * Construct a new acceptable delay strategy.
     *
     * @param TimeSpanInterface|integer|null $threshold The maximum allowable replication delay.
     * @param ClockInterface|null            $clock     The clock to use.
     */
    public function __construct($threshold = null, ClockInterface $clock = null)
    {
        parent::__construct($clock);

        if (null === $threshold) {
            $this->threshold = new Duration(3);
        } else {
            $this->threshold = $this->normalizeDuration($threshold);
        }
    }

    /**
     * Get the replication delay threshold.
     *
     * @return TimeSpanInterface The maximum allowable replication delay.
     */
    public function threshold()
    {
        return $this->threshold;
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
                'Selecting connection from pool {pool} with replication ' .
                    'delay less than the threshold {threshold}.',
                array(
                    'pool' => $pool->name(),
                    'threshold' => $this->threshold()->isoString(),
                )
            );
        }

        foreach ($pool->connections() as $connection) {
            if (!$replicationManager->isReplicating($connection)) {
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

            $delay = $replicationManager->delay($connection);

            if ($delay->isLessThanOrEqualTo($this->threshold())) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'selected from pool {pool}. ' .
                            'Replication delay of {delay} ' .
                            'is within the threshold {threshold}.',
                        array(
                            'connection' => $connection->name(),
                            'pool' => $pool->name(),
                            'delay' => $delay->isoString(),
                            'threshold' => $this->threshold()->isoString(),
                        )
                    );
                }

                return $connection;
            }

            if (null !== $logger) {
                $logger->debug(
                    'Connection {connection} ' .
                        'not selected from pool {pool}. ' .
                        'Replication delay of {delay} ' .
                        'is greater than the threshold {threshold}.',
                    array(
                        'connection' => $connection->name(),
                        'pool' => $pool->name(),
                        'delay' => $delay->isoString(),
                        'threshold' => $this->threshold()->isoString(),
                    )
                );
            }
        }

        if (null !== $logger) {
            $logger->warning(
                'No acceptable connection found in pool {pool}. ' .
                    'No connection found with replication delay within ' .
                    'the threshold {threshold}.',
                array(
                    'pool' => $pool->name(),
                    'threshold' => $this->threshold()->isoString(),
                )
            );
        }

        throw new NoConnectionAvailableException;
    }

    private $threshold;
}
