<?php
namespace Icecave\Manifold\QueryInspector;

interface QueryInspectorInterface
{
    /**
     * Determine if the given query performs only read operations.
     *
     * @param string $query The SQL query.
     *
     * @return boolean True if the query only reads; otherwise, false.
     */
    public function isReadOnly($query);

    /**
     * Determine the schema names referenced by a query.
     *
     * @param string $query The SQL query.
     *
     * @return array<string> The names of any schemata references by the query.
     */
    public function referencedSchemaNames($query);
}
