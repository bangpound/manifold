<?php
namespace Icecave\Manifold\Connection;

use Icecave\Collections\Vector;
use PDO;

/**
 * The interface implemented by connection pools.
 */
interface ConnectionPoolInterface
{
    /**
     * Get the connections.
     *
     * @return Vector<PDO> The connections.
     */
    public function connections();
}
