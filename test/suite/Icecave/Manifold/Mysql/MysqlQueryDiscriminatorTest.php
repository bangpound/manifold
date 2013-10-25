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

        $this->discriminator = new MysqlQueryDiscriminator(true);
    }

    public function testConstructor()
    {
        $this->assertTrue($this->discriminator->isAnsiQuotesEnabled());
    }

    public function testConstructorDefaults()
    {
        $this->discriminator = new MysqlQueryDiscriminator;

        $this->assertFalse($this->discriminator->isAnsiQuotesEnabled());
    }

    public function discriminateData()
    {
        //                                                       query                                                     isAnsiQuotesEnabled isWrite schema
        return array(
            'Select'                                             => array("SELECT * FROM foo.bar",                         null,               false,  'foo'),
            'Multi-line select'                                  => array("SELECT\n*\nFROM\n    foo.bar",                  null,               false,  'foo'),

            'Insert'                                             => array("INSERT INTO foo.bar VALUES (true)",             null,               true,   'foo'),
            'Multi-line insert'                                  => array("INSERT\nINTO\n    foo.bar\nVALUES\n(true)",     null,               true,   'foo'),
            'Insert ignore'                                      => array("INSERT IGNORE INTO foo.bar VALUES (true)",      null,               true,   'foo'),
            'Multi-line insert ignore'                           => array("INSERT\nIGNORE\nINTO\nfoo.bar\nVALUES\n(true)", null,               true,   'foo'),
            'Replace'                                            => array("REPLACE INTO foo.bar VALUES (true)",            null,               true,   'foo'),
            'Multi-line replace'                                 => array("REPLACE\nINTO\n    foo.bar\nVALUES\n(true)",    null,               true,   'foo'),

            'Update'                                             => array("UPDATE foo.bar SET baz = true",                 null,               true,   'foo'),
            'Multi-line update'                                  => array("UPDATE\nfoo.bar\nSET\nbaz\n=\ntrue",            null,               true,   'foo'),

            'Delete'                                             => array("DELETE FROM foo.bar",                           null,               true,   'foo'),
            'Multi-line delete'                                  => array("DELETE\nFROM\nfoo.bar",                         null,               true,   'foo'),

            'Backtick escaped name'                              => array("SELECT * FROM `fo``o`.`bar`",                   null,               false,  'fo`o'),
            'Double quote escaped name'                          => array("SELECT * FROM \"fo\"\"o\".\"bar\"",             null,               false,  '"fo""o"'),
            'Double quote escaped name with ANSI quotes enabled' => array("SELECT * FROM \"fo\"\"o\".\"bar\"",             true,               false,  'fo"o'),
            'Non-escapedd name with ANSI quotes enabled'         => array("SELECT * FROM foo.bar",                         true,               false,  'foo'),
        );
    }

    /**
     * @dataProvider discriminateData
     */
    public function testDiscriminate($query, $isAnsiQuotesEnabled, $isWrite, $schema)
    {
        $this->discriminator = new MysqlQueryDiscriminator($isAnsiQuotesEnabled);

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
