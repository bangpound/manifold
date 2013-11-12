<?php
namespace Icecave\Manifold\Replication;

/**
 * The interface implemented by query discriminators.
 */
interface QueryDiscriminatorInterface
{
    /**
     * Determine whether a given query is read-only, and which database it
     * primarily pertains to.
     *
     * @param string $query The query to discriminate.
     *
     * @return tuple<boolean,string|null>          A 2-tuple composed of a boolean that will be true if the query includes writes, and the name of the primary database affected, or null if the database could not be determined.
     * @throws Exception\UnsupportedQueryException If the query type is unsupported, or cannot be determined.
     */
    public function discriminate($query);
}
