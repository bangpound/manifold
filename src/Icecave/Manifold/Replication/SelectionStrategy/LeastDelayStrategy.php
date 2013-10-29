<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\ReplicationManagerInterface;
use PDO;

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
     * @param ReplicationManagerInterface $manager The replication manager to use.
     * @param ConnectionPoolInterface     $pool    The pool to select from.
     *
     * @return PDO                            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        ReplicationManagerInterface $manager,
        ConnectionPoolInterface $pool
    ) {
        $minDelay = null;
        $connection = null;
        foreach ($pool->connections() as $thisConnection) {
            if ($manager->isReplicating($thisConnection)) {
                $delay = $manager->delay($thisConnection);

                if (
                    (
                        null === $this->threshold() ||
                        $delay->isLessThanOrEqualTo($this->threshold())
                    ) &&
                    (null === $minDelay || $delay->isLessThan($minDelay))
                ) {
                    $minDelay = $delay;
                    $connection = $thisConnection;
                }
            }
        }

        if (null === $connection) {
            throw new NoConnectionAvailableException;
        }

        return $connection;
    }

    private $threshold;
}
