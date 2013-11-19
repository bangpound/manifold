<?php
namespace Icecave\Manifold\Connection\Container;

/**
 * Represents a read/write pair of connection containers.
 */
class ConnectionContainerPair implements ConnectionContainerPairInterface
{
    /**
     * Construct a new read/write connection container pair.
     *
     * @param ConnectionContainerInterface|null $write The write connection container, or null if the default should be used.
     * @param ConnectionContainerInterface|null $read  The read connection container, or null if the default should be used.
     */
    public function __construct(
        ConnectionContainerInterface $write = null,
        ConnectionContainerInterface $read = null
    ) {
        $this->write = $write;
        $this->read = $read;
    }

    /**
     * Get the write connection container.
     *
     * @return ConnectionContainerInterface|null The connection container, or null if the default should be used.
     */
    public function write()
    {
        return $this->write;
    }

    /**
     * Get the read connection container.
     *
     * @return ConnectionContainerInterface|null The connection container, or null if the default should be used.
     */
    public function read()
    {
        return $this->read;
    }

    private $write;
    private $read;
}
