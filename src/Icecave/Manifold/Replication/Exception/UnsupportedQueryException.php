<?php
namespace Icecave\Manifold\Replication\Exception;

use Exception;

/**
 * The supplied query is unsupported, or its type could not be determined.
 */
final class UnsupportedQueryException extends Exception
{
    /**
     * Construct a new unsupported query exception.
     *
     * @param string         $query    The query.
     * @param Exception|null $previous The cause, if available.
     */
    public function __construct($query, Exception $previous = null)
    {
        $this->query = $query;

        parent::__construct(
            sprintf('Unsupported query %s.', var_export($query, true)),
            0,
            $previous
        );
    }

    /**
     * Get the query.
     *
     * @return string The query.
     */
    public function query()
    {
        return $this->query;
    }

    private $query;
}
