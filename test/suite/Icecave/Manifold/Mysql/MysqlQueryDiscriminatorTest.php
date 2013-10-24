<?php
namespace Icecave\Manifold\Mysql;

use PHPUnit_Framework_TestCase;

/**
 * @covers \Icecave\Manifold\Mysql\MysqlQueryDiscriminator
 * @covers \Icecave\Manifold\Replication\AbstractQueryDiscriminator
 */
class MysqlQueryDiscriminatorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->discriminator = new MysqlQueryDiscriminator;
    }

    public function discriminateData()
    {
        //                              query                                                     isWrite schema
        return array(
            'Select'                    => array("SELECT * FROM foo.bar",                         false,  'foo'),
            'Multi-line select'         => array("SELECT\n*\nFROM\n    foo.bar",                  false,  'foo'),

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

            'Backtick escaped name'     => array("SELECT * FROM `fo``o`.`bar`",                   false,  'fo`o'),
            'Single quote escaped name' => array("SELECT * FROM 'f\\o\\'o'.'bar'",                false,  'fo\'o'),
            'Double quote escaped name' => array("SELECT * FROM \"f\\o\\\"o\".\"bar\"",           false,  'fo"o'),
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
            'Non-dotted name' => array("SELECT * FROM foo"),
            'DDL statement'   => array("SHOW CREATE foo.bar"),
        );
    }

    /**
     * @dataProvider discriminateUnsupportedQueryData
     */
    public function testDiscriminateUnsupported($query)
    {
        $this->setExpectedException('Icecave\Manifold\Replication\Exception\UnsupportedQueryException');
        $this->discriminator->discriminate($query);
    }
}
