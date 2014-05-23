<?php
namespace Icecave\Manifold\Replication;

use PHPUnit_Framework_TestCase;

class QueryDiscriminatorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->discriminator = new QueryDiscriminator(true);
    }

    public function discriminateData()
    {
        //                              query                                                     isWrite schema
        return array(
            'Select'                    => array("SELECT * FROM foo.bar",                         false,  'foo'),
            'Multi-line select'         => array("SELECT\n*\nFROM\n    foo.bar",                  false,  'foo'),
            'Non-dotted name select'    => array("SELECT * FROM foo",                             false,  null),
            'Expression select'         => array("SELECT 1",                                      false,  null),

            'Insert'                    => array("INSERT INTO foo.bar VALUES (true)",             true,   'foo'),
            'Multi-line insert'         => array("INSERT\nINTO\n    foo.bar\nVALUES\n(true)",     true,   'foo'),
            'Insert ignore'             => array("INSERT IGNORE INTO foo.bar VALUES (true)",      true,   'foo'),
            'Multi-line insert ignore'  => array("INSERT\nIGNORE\nINTO\nfoo.bar\nVALUES\n(true)", true,   'foo'),
            'Replace'                   => array("REPLACE INTO foo.bar VALUES (true)",            true,   'foo'),
            'Multi-line replace'        => array("REPLACE\nINTO\n    foo.bar\nVALUES\n(true)",    true,   'foo'),

            'Update'                    => array("UPDATE foo.bar SET baz = true",                 true,   'foo'),
            'Multi-line update'         => array("UPDATE\nfoo.bar\nSET\nbaz\n=\ntrue",            true,   'foo'),

            'Delete'                    => array("DELETE FROM foo.bar",                           true,   'foo'),
            'Multi-line delete'         => array("DELETE\nFROM\nfoo.bar",                         true,   'foo'),

            'Prefixed with whitespace'  => array(" \r \n SELECT * FROM foo.bar",                  false,  'foo'),
            'Prefixed with comment'     => array("/* baz*qux/doom */ \n SELECT * FROM foo.bar",   false,  'foo'),

            'Double quote escaped name' => array("SELECT * FROM \"fo\"\"o\".\"bar\"",             false,  'fo"o'),
        );
    }

    /**
     * @dataProvider discriminateData
     */
    public function testDiscriminate($query, $isWrite, $schema)
    {
        $this->assertSame(array($isWrite, $schema), $this->discriminator->discriminate($query));
    }

    public function discriminateUnsupportedQueryData()
    {
        return array(
            'Non-dotted name outside select' => array("DELETE FROM foo"),
            'DDL statement'                  => array("SHOW CREATE foo.bar"),
        );
    }

    /**
     * @dataProvider discriminateUnsupportedQueryData
     */
    public function testDiscriminateUnsupported($query)
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UnsupportedQueryException');
        $this->discriminator->discriminate($query);
    }
}
