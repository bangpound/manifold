<?php
namespace Icecave\Manifold\Query;

/**
 * Normalizes SQL queries.
 *
 * This class is intended to allow nice query formatting in PHP strings that can
 * be collapsed to a single line before execution.
 *
 * Note that this class should be used with caution. It works best with prepared
 * statements, but may mangle queries with whitespace in quoted strings, queries
 * with single-line comments, and other unforeseen cases. Always check the
 * output before use.
 */
class QueryNormalizer implements QueryNormalizerInterface
{
    /**
     * Get a static instance of this normalizer.
     *
     * @return QueryNormalizerInterface The static normalizer.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Normalize the supplied query.
     *
     * @param string $query The query to normalize.
     *
     * @return string The normalized query.
     */
    public function normalizeQuery($query)
    {
        return trim(preg_replace('/\s*(?:\r|\n|\r\n)\s\s+/', ' ', $query));
    }

    private static $instance;
}
