<?php
namespace Icecave\Manifold\Query;

use Eloquent\Liberator\Liberator;
use PHPUnit_Framework_TestCase;

class QueryNormalizerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->normalizer = new QueryNormalizer;
    }

    public function normalizeData()
    {
        return array(
            'Empty' => array('', ''),
            'Typical example' => array(
                'SELECT
                    a.id AS a_id,
                    b.name AS b_name
                FROM database.table_a AS a
                INNER JOIN database.table_b AS b
                    ON b.id = a.b_id
                LEFT JOIN database.table_c AS c
                    ON c.name = b.c_name
                    AND c.scope = "foo  bar"
                LEFT JOIN database.table_d AS d
                    ON d.user_id = a.id
                    AND d.tag_id = t.id' . '  ' . '
                WHERE d.tag_id IS NULL ',
                'SELECT a.id AS a_id, b.name AS b_name FROM database.table_a AS a INNER JOIN database.table_b AS b ON b.id = a.b_id LEFT JOIN database.table_c AS c ON c.name = b.c_name AND c.scope = "foo  bar" LEFT JOIN database.table_d AS d ON d.user_id = a.id AND d.tag_id = t.id WHERE d.tag_id IS NULL',
            ),
        );
    }

    /**
     * @dataProvider normalizeData
     */
    public function testNormalize($query, $expected)
    {
        $this->assertSame($expected, $this->normalizer->normalizeQuery($query));
    }

    public function testInstance()
    {
        $class = get_class($this->normalizer);
        $liberatedClass = Liberator::liberateClass($class);
        $liberatedClass->instance = null;
        $actual = $class::instance();

        $this->assertInstanceOf($class, $actual);
        $this->assertSame($actual, $class::instance());
    }
}
