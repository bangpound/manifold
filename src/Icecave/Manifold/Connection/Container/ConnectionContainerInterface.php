<?php
namespace Icecave\Manifold\Connection\Container;

use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\ConnectionInterface;

/**
 * The interface implemented by connection containers.
 */
interface ConnectionContainerInterface
{
    /**
     * Get the connection container name.
     *
     * @return string The connection container name.
     */
    public function name();

    /**
     * Get the connections.
     *
     * @return Vector<ConnectionInterface> The connections.
     */
    public function connections();
}
