<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;
use Icecave\Manifold\TypeCheck\TypeCheck;
use PDO;

/**
 * The supplied connection does not exist in this replication tree.
 */
class UnknownConnectionException extends Exception
{
    /**
     * Construct a new unknown connection exception.
     *
     * @param PDO            $connection The unknown connection.
     * @param Exception|null $previous   The cause, if available.
     */
    public function __construct(PDO $connection, Exception $previous = null)
    {
        TypeCheck::get(__CLASS__, func_get_args());

        $this->connection = $connection;

        parent::__construct('Unknown connection.', 0, $previous);
    }

    /**
     * Get the unknown connection.
     *
     * @return PDO The connection.
     */
    public function connection()
    {
        return $this->connection;
    }

    private $connection;
}
