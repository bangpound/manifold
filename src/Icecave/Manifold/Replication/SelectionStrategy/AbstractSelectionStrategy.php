<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\Clock\SystemClock;
use Icecave\Chrono\DateTime;
use Icecave\Chrono\TimePointInterface;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;

/**
 * An abstract base class for implementing connection pool member selection
 * strategies.
 */
abstract class AbstractSelectionStrategy implements SelectionStrategyInterface
{
    /**
     * Construct a new connection pool member selection strategy.
     *
     * @param ClockInterface|null $clock The clock to use.
     */
    public function __construct(ClockInterface $clock = null)
    {
        if (null === $clock) {
            $clock = new SystemClock;
        }

        $this->clock = $clock;
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
     * Generate a string representation of this strategy.
     *
     * @return string The generated string representation of this strategy.
     */
    public function __toString()
    {
        return $this->string();
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

    private $clock;
}
