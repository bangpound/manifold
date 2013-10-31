<?php
namespace Icecave\Manifold\Replication;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\Clock\SystemClock;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Chrono\Timer\Timer;
use Icecave\Chrono\Timer\TimerInterface;
use PDO;

/**
 * An abstract base class for implementing replication managers.
 */
abstract class AbstractReplicationManager implements ReplicationManagerInterface
{
    /**
     * Construct a new replication manager.
     *
     * @param ReplicationTreeInterface $tree  The replication tree upon which this manager operates.
     * @param ClockInterface|null      $clock The clock to use.
     * @param TimerInterface|null      $timer The timer to use.
     */
    public function __construct(
        ReplicationTreeInterface $tree,
        ClockInterface $clock = null,
        TimerInterface $timer = null
    ) {
        if (null === $clock) {
            $clock = new SystemClock;
        }
        if (null === $timer) {
            $timer = new Timer($clock);
        }

        $this->tree = $tree;
        $this->clock = $clock;
        $this->timer = $timer;
    }

    /**
     * Fetch the replication tree upon which this manager operates.
     *
     * @return ReplicationTreeInterface The replication tree upon which this manager operates.
     */
    public function tree()
    {
        return $this->tree;
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
     * Get the timer.
     *
     * @return TimerInterface The timer.
     */
    public function timer()
    {
        return $this->timer;
    }

    /**
     * Fetch a replication slave's delay.
     *
     * This method traverses up the replication path, adding up the replication
     * delay at each link to get a total amount.
     *
     * An optional threshold can be specified. This will prevent the manager
     * from continuing to add up replication delay from further up the path if
     * the threshold has already been surpassed. In this case, null will be
     * returned instead of a duration. This allows for improved performance if
     * the delay must be below a threshold to be useful.
     *
     * @param PDO                            $slaveConnection       The replication slave.
     * @param TimeSpanInterface|integer|null $threshold             The maximum allowable replication delay, or null to allow any amount of delay.
     * @param PDO                            $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return Duration|null                     The replication delay between $masterConnection and $slaveConnection, or null if a threshold is passed, and the duration surpasses that threshold.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delay(
        PDO $slaveConnection,
        $threshold = null,
        PDO $masterConnection = null
    ) {
        $threshold = $this->normalizeDuration($threshold);

        $path = $this->tree()->replicationPath(
            $slaveConnection,
            $masterConnection
        );
        if (null === $path) {
            throw new Exception\NotReplicatingException($slaveConnection);
        }

        $totalDelay = new Duration(0);
        foreach ($path as $element) {
            list($masterConnection, $slaveConnection) = $element;

            $delay = $this->amountBehindMaster(
                $masterConnection,
                $slaveConnection
            );
            if (null === $delay) {
                throw new Exception\NotReplicatingException($slaveConnection);
            }

            $totalDelay = $totalDelay->add($delay);

            if (null !== $threshold && $threshold->isLessThan($totalDelay)) {
                return null;
            }
        }

        return $totalDelay;
    }

    /**
     * Check if a slave's replication delay is within the given threshold.
     *
     * This method traverses up the replication path, adding up the replication
     * delay at each link to get a total amount. Traversal stops immediately if
     * the delay surpasses the threshold.
     *
     * @param TimeSpanInterface|integer $threshold             The threshold delay.
     * @param PDO                       $slaveConnection       The replication slave.
     * @param PDO                       $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           True if the slave's replication delay is less than or equal to $threshold.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function delayWithin(
        $threshold,
        PDO $slaveConnection,
        PDO $masterConnection = null
    ) {
        $threshold = $this->normalizeDuration($threshold);
        $path = $this->tree()->replicationPath(
            $slaveConnection,
            $masterConnection
        );
        if (null === $path) {
            throw new Exception\NotReplicatingException($slaveConnection);
        }

        $totalDelay = new Duration(0);
        foreach ($path as $element) {
            list($masterConnection, $slaveConnection) = $element;

            $delay = $this->amountBehindMaster(
                $masterConnection,
                $slaveConnection
            );
            if (null === $delay) {
                throw new Exception\NotReplicatingException($slaveConnection);
            }

            $totalDelay = $totalDelay->add($delay);

            if ($threshold->isLessThan($totalDelay)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a slave is replicating.
     *
     * This function will return false if any of the links in the replication
     * path between $masterConnection and $slaveConnection are not replicating.
     *
     * @param PDO      $slaveConnection  The replication slave.
     * @param PDO|null $masterConnection The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           True if $slaveConnection is replicating; otherwise, false.
     * @throws Exception\NotReplicatingException If $slaveConnection is not a replication slave of $masterConnection.
     */
    public function isReplicating(
        PDO $slaveConnection,
        PDO $masterConnection = null
    ) {
        $path = $this->tree()->replicationPath(
            $slaveConnection,
            $masterConnection
        );
        if (null === $path) {
            return false;
        }

        foreach ($path as $element) {
            list($masterConnection, $slaveConnection) = $element;

            $delay = $this->amountBehindMaster(
                $masterConnection,
                $slaveConnection
            );
            if (null === $delay) {
                return false;
            }
        }

        return true;
    }

    /**
     * Wait for a slave replication to catch up to the current point on the
     * given master.
     *
     * This method traverses up the replication path, waiting for each link to
     * catch up to its master. If a timeout is specified, this method will
     * return false once the total time spent waiting exceeds this timeout.
     *
     * @param PDO                            $slaveConnection       The replication slave.
     * @param TimeSpanInterface|integer|null $timeout               The maximum time to wait, or null to wait indefinitely.
     * @param PDO                            $masterConnection|null The replication master to check against, or null to use the replication root.
     *
     * @return boolean                           False if the wait operation times out before completion; otherwise, true.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    public function wait(
        PDO $slaveConnection,
        $timeout = null,
        PDO $masterConnection = null
    ) {
        if ($this->tree()->isRoot($slaveConnection)) {
            return true;
        }

        $timeout = $this->normalizeDuration($timeout);

        $path = $this->tree()->replicationPath(
            $slaveConnection,
            $masterConnection
        );
        if (null === $path) {
            throw new Exception\NotReplicatingException($slaveConnection);
        }

        if (null !== $timeout) {
            $this->timer()->reset();
            $this->timer()->start();
        }

        foreach ($path as $element) {
            if (null !== $timeout) {
                $elapsed = new Duration(intval($this->timer()->elapsed()));
            }

            list($masterConnection, $slaveConnection) = $element;

            if (null === $timeout) {
                $result = $this->doWait($masterConnection, $slaveConnection);
            } else {
                $result = $this->doWait(
                    $masterConnection,
                    $slaveConnection,
                    $timeout->subtract($elapsed)
                );
            }
            if (!$result) {
                return false;
            }

            if (null !== $timeout && $elapsed->isGreaterThan($timeout)) {
                return false;
            }
        }

        return true;
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
     * Determine how far the supplied connection is behind its master.
     *
     * This method is not expected to handle multiple links in the replication
     * path between $masterConnection and $slaveConnection. Implementations can
     * safely assume that $masterConnection is the direct master of
     * $slaveConnection.
     *
     * @param PDO $masterConnection The replication master to check against.
     * @param PDO $slaveConnection  The replication slave.
     *
     * @return Duration|null The amount of time behind master, or null if $slaveConnection is not replicating from $masterConnection.
     */
    abstract protected function amountBehindMaster(
        PDO $masterConnection,
        PDO $slaveConnection
    );

    /**
     * Wait for a slave replication to catch up to the current point on the
     * given master.
     *
     * This method is not expected to handle multiple links in the replication
     * path between $masterConnection and $slaveConnection. Implementations can
     * safely assume that $masterConnection is the direct master of
     * $slaveConnection.
     *
     * @param PDO                    $masterConnection The replication master to check against.
     * @param PDO                    $slaveConnection  The replication slave.
     * @param TimeSpanInterface|null $timeout          The maximum time to wait, or null to wait indefinitely.
     *
     * @return boolean                           False if the wait operation times out before completion; otherwise, true.
     * @throws Exception\NotReplicatingException If $slaveConnection is not replicating from $masterConnection.
     */
    abstract protected function doWait(
        PDO $masterConnection,
        PDO $slaveConnection,
        TimeSpanInterface $timeout = null
    );

    private $tree;
    private $clock;
    private $timer;
}
