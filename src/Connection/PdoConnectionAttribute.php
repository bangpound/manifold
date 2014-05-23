<?php
namespace Icecave\Manifold\Connection;

use Eloquent\Constance\AbstractClassConstant;

/**
 * An enumeration of PDO connection attributes.
 */
final class PdoConnectionAttribute extends AbstractClassConstant
{
    /**
     * The class to inspect for constants.
     */
    const CONSTANCE_CLASS = 'PDO';

    /**
     * The expression used to match constant names that should be included in
     * this enumeration.
     */
    const CONSTANCE_PATTERN = '{^ATTR_}';
}
