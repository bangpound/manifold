<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Selects the connection with the least replication delay.
 */
class LeastDelayStrategy extends AbstractSelectionStrategy
{
    /**
     * Construct a new least delay strategy.
     *
     * @param TimeSpanInterface|integer|null $threshold The maximum allowable replication delay, or null to allow any amount of delay.
     * @param ClockInterface|null            $clock     The clock to use.
     */
    public function __construct($threshold = null, ClockInterface $clock = null)
    {
        parent::__construct($clock);

        $this->threshold = $this->normalizeDuration($threshold);
    }

    /**
     * Get the replication delay threshold.
     *
     * @return TimeSpanInterface|null The maximum allowable replication delay, or null if no threshold is in use.
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
            if (null === $this->threshold()) {
                $logger->debug(
                    'Selecting connection with least replication delay ' .
                        'from pool {pool}.',
                    array('pool' => $pool->name())
                );
            } else {
                $logger->debug(
                    'Selecting connection with least replication delay ' .
                        'from pool {pool}, where replication delay is ' .
                        'less than threshold {threshold}.',
                    array(
                        'pool' => $pool->name(),
                        'threshold' => $this->threshold()->isoString(),
                    )
                );
            }
        }

        $minDelay = null;
        $connection = null;
        foreach ($pool->connections() as $thisConnection) {
            $delay = $replicationManager->delay($thisConnection);

            if (null === $delay) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'not selected from pool {pool}. ' .
                            'The connection is not replicating.',
                        array(
                            'connection' => $thisConnection->name(),
                            'pool' => $pool->name(),
                        )
                    );
                }

                continue;
            }

            if (
                null !== $this->threshold() &&
                $delay->isGreaterThan($this->threshold())
            ) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'not selected from pool {pool}. ' .
                            'Replication delay of {delay} ' .
                            'is greater than the threshold {threshold}.',
                        array(
                            'connection' => $thisConnection->name(),
                            'pool' => $pool->name(),
                            'delay' => $delay->isoString(),
                            'threshold' => $this->threshold()->isoString(),
                        )
                    );
                }

                continue;
            }

            if (null !== $logger) {
                $logger->debug(
                    'Connection {connection} from pool {pool} has a ' .
                        'replication delay of {delay}.',
                    array(
                        'connection' => $thisConnection->name(),
                        'pool' => $pool->name(),
                        'delay' => $delay->isoString(),
                    )
                );
            }

            if (null === $minDelay || $delay->isLessThan($minDelay)) {
                $minDelay = $delay;
                $connection = $thisConnection;
            }
        }

        if (null === $connection) {
            if (null !== $logger) {
                if (null === $this->threshold()) {
                    $logger->warning(
                        'No acceptable connection found in pool {pool}.',
                        array('pool' => $pool->name())
                    );
                } else {
                    $logger->warning(
                        'No acceptable connection found in pool {pool}. ' .
                            'No connection found with replication delay ' .
                            'within the threshold {threshold}.',
                        array(
                            'pool' => $pool->name(),
                            'threshold' => $this->threshold()->isoString(),
                        )
                    );
                }
            }

            throw new NoConnectionAvailableException;
        }

        if (null !== $logger) {
            $logger->debug(
                'Connection {connection} selected from pool {pool}. ' .
                    'Connection has the least replication delay ' .
                    'of all suitable candidates.',
                array(
                    'connection' => $connection->name(),
                    'pool' => $pool->name(),
                )
            );
        }

        return $connection;
    }

    private $threshold;
}
