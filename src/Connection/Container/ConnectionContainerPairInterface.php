<?php
namespace Icecave\Manifold\Connection\Container;

/**
 * The interface implemented by connection container read/write pairs.
 */
interface ConnectionContainerPairInterface
{
    /**
     * Get the write connection container.
     *
     * @return ConnectionContainerInterface|null The connection container, or null if the default should be used.
     */
    public function write();

    /**
     * Get the read connection container.
     *
     * @return ConnectionContainerInterface|null The connection container, or null if the default should be used.
     */
    public function read();
}
