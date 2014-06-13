<?php
namespace Icecave\Manifold\Query;

/**
 * The interface implemented by query normalizers.
 */
interface QueryNormalizerInterface
{
    /**
     * Normalize the supplied query.
     *
     * @param string $query The query to normalize.
     *
     * @return string The normalized query.
     */
    public function normalizeQuery($query);
}
