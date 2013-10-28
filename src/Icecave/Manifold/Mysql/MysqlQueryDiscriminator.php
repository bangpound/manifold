<?php
namespace Icecave\Manifold\Mysql;

use Icecave\Manifold\Replication\QueryDiscriminator;

/**
 * A query discriminator for MySQL query syntax.
 */
class MysqlQueryDiscriminator extends QueryDiscriminator
{
    /**
     * Construct a new MySQL query discriminator.
     *
     * @param boolean|null $isAnsiQuotesEnabled True if ANSI quotes support should be enabled.
     */
    public function __construct($isAnsiQuotesEnabled = null)
    {
        if (null === $isAnsiQuotesEnabled) {
            $isAnsiQuotesEnabled = false;
        }

        $this->isAnsiQuotesEnabled = $isAnsiQuotesEnabled;
    }

    /**
     * Returns true if ANSI quotes support is enabled.
     *
     * @return boolean True if ANSI quotes support is enabled.
     */
    public function isAnsiQuotesEnabled()
    {
        return $this->isAnsiQuotesEnabled;
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
        if ('`' === $identifier[0]) {
            return str_replace('``', '`', substr($identifier, 1, -1));
        }

        if ($this->isAnsiQuotesEnabled()) {
            return parent::unescapeIdentifier($identifier);
        }

        return $identifier;
    }

    private $isAnsiQuotesEnabled;
}
