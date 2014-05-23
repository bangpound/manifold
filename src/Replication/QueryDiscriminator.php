<?php
namespace Icecave\Manifold\Replication;

/**
 * A generic query discriminator.
 */
class QueryDiscriminator implements QueryDiscriminatorInterface
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
    public function discriminate($query)
    {
        $query = preg_replace('{^(?:/\*.*?\*/)?\s*}s', '', $query);
        $isWrite = true;

        if (preg_match('{^SELECT\s}i', $query)) {
            $isWrite = false;

            if (
                preg_match('{.*\s+FROM\s+([^.]+)\.}si', $query, $matches, 0, 7)
            ) {
                $database = $matches[1];
            } else {
                $database = null;
            }
        } elseif (
            preg_match(
                '{^(?:INSERT|INSERT\s+IGNORE|REPLACE)\s+INTO\s+([^.]+)\.}i',
                $query,
                $matches
            )
        ) {
            $database = $matches[1];
        } elseif (preg_match('{^UPDATE\s+([^.]+)\.}i', $query, $matches)) {
            $database = $matches[1];
        } elseif (
            preg_match('{^DELETE\s+FROM\s+([^.]+)\.}i', $query, $matches)
        ) {
            $database = $matches[1];
        } else {
            throw new Exception\UnsupportedQueryException($query);
        }

        return array($isWrite, $this->unescapeIdentifier($database));
    }

    /**
     * Returns an escaped identifier to its original plaintext form.
     *
     * @param string $identifier The escaped identifier.
     *
     * @return string The plaintext identifier.
     */
    protected function unescapeIdentifier($identifier)
    {
        if ('"' === $identifier[0]) {
            return str_replace('""', '"', substr($identifier, 1, -1));
        }

        return $identifier;
    }
}
