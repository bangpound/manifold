<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimeSpan\Duration;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerInterface;
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
     * Get a single connection from a container.
     *
     * @param ReplicationManagerInterface  $replicationManager The replication manager to use.
     * @param ConnectionContainerInterface $container          The container to select from.
     * @param LoggerInterface|null         $logger             The logger to use.
     *
     * @return ConnectionInterface            The selected connection.
     * @throws NoConnectionAvailableException If no connection is available for selection.
     */
    public function select(
        ReplicationManagerInterface $replicationManager,
        ConnectionContainerInterface $container,
        LoggerInterface $logger = null
    ) {
        if (null !== $logger) {
            $logger->debug(
                'Selecting connection from container {container} with ' .
                    'replication delay less than the threshold {threshold}.',
                array(
                    'container' => $container->name(),
                    'threshold' => $this->threshold()->isoString(),
                )
            );
        }

        foreach ($container->connections() as $connection) {
            $delay = $replicationManager->delay(
                $connection,
                $this->threshold()
            );

            if (null === $delay) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'not selected from container {container}. ' .
                            'The connection is not replicating.',
                        array(
                            'connection' => $connection->name(),
                            'container' => $container->name(),
                        )
                    );
                }

                continue;
            }

            if ($delay->isLessThanOrEqualTo($this->threshold())) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'selected from container {container}. ' .
                            'Replication delay of {delay} ' .
                            'is within the threshold {threshold}.',
                        array(
                            'connection' => $connection->name(),
                            'container' => $container->name(),
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
                        'not selected from container {container}. ' .
                        'Replication delay is at least {delay}, ' .
                        'and is greater than the threshold {threshold}.',
                    array(
                        'connection' => $connection->name(),
                        'container' => $container->name(),
                        'delay' => $delay->isoString(),
                        'threshold' => $this->threshold()->isoString(),
                    )
                );
            }
        }

        if (null !== $logger) {
            $logger->warning(
                'No acceptable connection found in container {container}. ' .
                    'No connection found with replication delay within ' .
                    'the threshold {threshold}.',
                array(
                    'container' => $container->name(),
                    'threshold' => $this->threshold()->isoString(),
                )
            );
        }

        throw new NoConnectionAvailableException();
    }

    /**
     * Generate a string representation of this strategy.
     *
     * @return string The generated string representation of this strategy.
     */
    public function string()
    {
        return sprintf(
            'Any replicating connection with a delay less than %s.',
            var_export($this->threshold()->isoString(), true)
        );
    }

    private $threshold;
}
