<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\Clock\SystemClock;
use Icecave\Chrono\DateTime;
use Icecave\Chrono\TimePointInterface;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use PDO;

class ConnectionSelector implements ConnectionSelectorInterface
{
    /**
     * Construct a new connection selector.
     *
     * @param ReplicationManagerInterface $manager The replication manager to use.
     * @param ClockInterface|null         $clock   The clock to use.
     */
    public function __construct(
        ReplicationManagerInterface $manager,
        ClockInterface $clock = null
    ) {
        if (null === $clock) {
            $clock = new SystemClock;
        }

        $this->manager = $manager;
        $this->clock = $clock;
    }

    /**
     * Get the replication manager.
     *
     * @return ReplicationManagerInterface The replication manager.
     */
    public function manager()
    {
        return $this->manager;
    }

    /**
     * Get the clock.
     *
     * @return ClockInterface The clock.
     */
    public function clock()
    {
        return $this->clock;
    }

    /**
     * Get a single connection from a pool.
     *
     * No replication delay threshold is enforced, but replication must be running.
     *
     * @param ConnectionPoolInterface $pool The pool to select from.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(ConnectionPoolInterface $pool)
    {
        foreach ($pool->connections() as $connection) {
            if ($this->manager()->isReplicating($connection)) {
                return $connection;
            }
        }

        throw new Exception\NoConnectionAvailableException;
    }

    /**
     * Get a single connection from a pool by selecting the connection with the
     * least replication delay.
     *
     * @param ConnectionPoolInterface        $pool      The pool to select from.
     * @param TimeSpanInterface|integer|null $threshold The maximum allowable replication delay, or null to allow any amount of delay.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function selectByLeastDelay(
        ConnectionPoolInterface $pool,
        $threshold = null
    ) {
        $threshold = $this->normalizeDuration($threshold);

        $minDelay = null;
        $connection = null;
        foreach ($pool->connections() as $thisConnection) {
            if ($this->manager()->isReplicating($thisConnection)) {
                $delay = $this->manager()->delay($thisConnection);

                if (
                    (
                        null === $threshold ||
                        $delay->isLessThanOrEqualTo($threshold)
                    ) &&
                    (null === $minDelay || $delay->isLessThan($minDelay))
                ) {
                    $minDelay = $delay;
                    $connection = $thisConnection;
                }
            }
        }

        if (null === $connection) {
            throw new Exception\NoConnectionAvailableException;
        }

        return $connection;
    }

    /**
     * Get a single connection from a pool by selecting any connection with a
     * replication delay less than the specified maximum.
     *
     * @param ConnectionPoolInterface   $pool      The pool to select from.
     * @param TimeSpanInterface|integer $threshold The maximum allowable replication delay.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function selectByAcceptableDelay(
        ConnectionPoolInterface $pool,
        $threshold
    ) {
        $threshold = $this->normalizeDuration($threshold);

        foreach ($pool->connections() as $connection) {
            if (
                $this->manager()->isReplicating($connection) &&
                $this->manager()->delay($connection)
                        ->isLessThanOrEqualTo($threshold)
            ) {
                return $connection;
            }
        }

        throw new Exception\NoConnectionAvailableException;
    }

    /**
     * Get a single connection from a pool by selecting any connection that is
     * at least up to date with a given time point.
     *
     * @param ConnectionPoolInterface    $pool      The pool to select from.
     * @param TimePointInterface|integer $timePoint The minimum cut-off time for replication delay.
     *
     * @return PDO                                      The selected connection.
     * @throws Exception\NoConnectionAvailableException If no connection is available for selection.
     */
    public function selectByTime(ConnectionPoolInterface $pool, $timePoint)
    {
        $timePoint = $this->normalizeTimePoint($timePoint);
        $now = $this->clock()->localDateTime();

        foreach ($pool->connections() as $connection) {
            if (
                $this->manager()->isReplicating($connection) &&
                $now->subtract($this->manager()->delay($connection))
                    ->isGreaterThanOrEqualTo($timePoint)
            ) {
                return $connection;
            }
        }

        throw new Exception\NoConnectionAvailableException;
    }

    /**
     * Normalizes various representations of a duration into a Duration
     * instance.
     *
     * @param TimeSpanInterface|integer|null $duration The duration to normalize.
     *
     * @return Duration|null The normalized duration.
     */
    protected function normalizeDuration($duration)
    {
        if (null === $duration) {
            return null;
        } elseif ($duration instanceof TimeSpanInterface) {
            return $duration->resolveToDuration(
                $this->clock()->localDateTime()
            );
        }

        return new Duration($duration);
    }

    /**
     * Normalizes various representations of a time point into a
     * TimePointInterface instance.
     *
     * @param TimePointInterface|null $timePoint The time point to normalize.
     *
     * @return TimePointInterface The normalized time point.
     */
    protected function normalizeTimePoint($timePoint)
    {
        if ($timePoint instanceof TimePointInterface) {
            return $timePoint;
        }

        return DateTime::fromUnixTime($timePoint);
    }

    private $manager;
    private $clock;
}
