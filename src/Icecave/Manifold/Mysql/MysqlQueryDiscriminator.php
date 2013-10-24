<?php
namespace Icecave\Manifold\Mysql;

use Icecave\Manifold\Replication\AbstractQueryDiscriminator;

/**
 * A query discriminator for MySQL query syntax.
 */
class MysqlQueryDiscriminator extends AbstractQueryDiscriminator
{
    /**
     * Returns an escaped identifier to its original plaintext form.
     *
     * @param string $identifier The escaped identifier.
     *
     * @return string The plaintext identifier.
     */
    protected function unescapeIdentifier($identifier)
    {
        if ('`' === $identifier[0]) {
            return str_replace('``', '`', substr($identifier, 1, -1));
        }

        return parent::unescapeIdentifier($identifier);
    }
}
