<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimePointInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use PDO;

/**
 * Selects the first connection that is up-to-date
 */
class TimePointStrategy extends AbstractSelectionStrategy
{
    /**
     * Construct a new time point strategy.
     *
     * @param ReplicationManagerInterface     $manager   The replication manager to use.
     * @param TimePointInterface|integer|null $timePoint The minimum cut-off time for replication delay.
     * @param ClockInterface|null             $clock     The clock to use.
     */
    public function __construct(
        ReplicationManagerInterface $manager,
        $timePoint = null,
        ClockInterface $clock = null
    ) {
        parent::__construct($manager, $clock);

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
     * @param ConnectionPoolInterface $pool The pool to select from.
     *
     * @return PDO                            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(ConnectionPoolInterface $pool)
    {
        $now = $this->clock()->localDateTime();

        foreach ($pool->connections() as $connection) {
            if (
                $this->manager()->isReplicating($connection) &&
                $now->subtract($this->manager()->delay($connection))
                    ->isGreaterThanOrEqualTo($this->timePoint())
            ) {
                return $connection;
            }
        }

        throw new NoConnectionAvailableException;
    }

    private $timePoint;
}