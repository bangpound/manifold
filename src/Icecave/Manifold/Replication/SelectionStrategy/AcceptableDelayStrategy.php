<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use PDO;

/**
 * Selects the first connection with a replication delay less than the specified
 * maximum delay.
 */
class AcceptableDelayStrategy extends AbstractSelectionStrategy
{
    /**
     * Construct a new acceptable delay strategy.
     *
     * @param ReplicationManagerInterface    $manager   The replication manager to use.
     * @param TimeSpanInterface|integer|null $threshold The maximum allowable replication delay.
     * @param ClockInterface|null            $clock     The clock to use.
     */
    public function __construct(
        ReplicationManagerInterface $manager,
        $threshold = null,
        ClockInterface $clock = null
    ) {
        parent::__construct($manager, $clock);

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
     * @param ConnectionPoolInterface $pool The pool to select from.
     *
     * @return PDO                            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(ConnectionPoolInterface $pool)
    {
        foreach ($pool->connections() as $connection) {
            if (
                $this->manager()->isReplicating($connection) &&
                $this->manager()->delay($connection)
                        ->isLessThanOrEqualTo($this->threshold())
            ) {
                return $connection;
            }
        }

        throw new NoConnectionAvailableException;
    }

    private $threshold;
}
