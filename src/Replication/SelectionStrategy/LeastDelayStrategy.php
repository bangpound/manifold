<?php
namespace Icecave\Manifold\Replication\SelectionStrategy;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\TimeSpan\TimeSpanInterface;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerInterface;
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
            if (null === $this->threshold()) {
                $logger->debug(
                    'Selecting connection with least replication delay ' .
                        'from container {container}.',
                    array('container' => $container->name())
                );
            } else {
                $logger->debug(
                    'Selecting connection with least replication delay ' .
                        'from container {container}, where replication delay ' .
                        'is less than threshold {threshold}.',
                    array(
                        'container' => $container->name(),
                        'threshold' => $this->threshold()->isoString(),
                    )
                );
            }
        }

        $minDelay = null;
        $connection = null;
        foreach ($container->connections() as $thisConnection) {
            $delay = $replicationManager->delay(
                $thisConnection,
                $this->threshold()
            );

            if (null === $delay) {
                if (null !== $logger) {
                    $logger->debug(
                        'Connection {connection} ' .
                            'not selected from container {container}. ' .
                            'The connection is not replicating.',
                        array(
                            'connection' => $thisConnection->name(),
                            'container' => $container->name(),
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
                            'not selected from container {container}. ' .
                            'Replication delay is at least {delay}, ' .
                            'and is greater than the threshold {threshold}.',
                        array(
                            'connection' => $thisConnection->name(),
                            'container' => $container->name(),
                            'delay' => $delay->isoString(),
                            'threshold' => $this->threshold()->isoString(),
                        )
                    );
                }

                continue;
            }

            if (null !== $logger) {
                $logger->debug(
                    'Connection {connection} from container {container} has ' .
                        'a replication delay of {delay}.',
                    array(
                        'connection' => $thisConnection->name(),
                        'container' => $container->name(),
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
                        'No acceptable connection found in container ' .
                            '{container}.',
                        array('container' => $container->name())
                    );
                } else {
                    $logger->warning(
                        'No acceptable connection found in container ' .
                            '{container}. No connection found with ' .
                            'replication delay within the threshold ' .
                            '{threshold}.',
                        array(
                            'container' => $container->name(),
                            'threshold' => $this->threshold()->isoString(),
                        )
                    );
                }
            }

            throw new NoConnectionAvailableException;
        }

        if (null !== $logger) {
            $logger->debug(
                'Connection {connection} selected from container ' .
                    '{container}. Connection has the least replication delay ' .
                    'of all suitable candidates.',
                array(
                    'connection' => $connection->name(),
                    'container' => $container->name(),
                )
            );
        }

        return $connection;
    }

    /**
     * Generate a string representation of this strategy.
     *
     * @return string The generated string representation of this strategy.
     */
    public function string()
    {
        if (null === $this->threshold()) {
            return 'The connection with the least replication delay.';
        }

        return sprintf(
            'The connection with the least replication delay, ' .
                'but also less than %s.',
            var_export($this->threshold()->isoString(), true)
        );
    }

    private $threshold;
}
