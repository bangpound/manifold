<?php
namespace Icecave\Manifold\Pdo;

use PDO;
use Phake;
use PHPUnit_Framework_TestCase;

class AbstractPdoProxyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = Phake::mock('PDO');

        $this->proxy = Phake::partialMock(__NAMESPACE__ . '\AbstractPdoProxy');

        Phake::when($this->proxy)
            ->innerConnection()
            ->thenReturn($this->connection);
    }

    public function testExec()
    {
        Phake::when($this->connection)
            ->exec(Phake::anyParameters())
            ->thenReturn('<exec result>');

        $result = $this->proxy->exec('SELECT * FROM foo');

        Phake::verify($this->connection)->exec('SELECT * FROM foo');

        $this->assertSame($result, '<exec result>');
    }

    public function testPrepare()
    {
        Phake::when($this->connection)
            ->prepare(Phake::anyParameters())
            ->thenReturn('<prepare result>');

        $driverOptions = array(
            PDO::MYSQL_ATTR_LOCAL_INFILE => true
        );

        $result = $this->proxy->prepare('SELECT * FROM foo', $driverOptions);

        Phake::verify($this->connection)->prepare('SELECT * FROM foo', $driverOptions);

        $this->assertSame($result, '<prepare result>');
    }

    public function testQuery()
    {
        Phake::when($this->connection)
            ->query(Phake::anyParameters())
            ->thenReturn('<query result>');

        $result = $this->proxy->query('SELECT * FROM foo');

        Phake::verify($this->connection)->query('SELECT * FROM foo');

        $this->assertSame($result, '<query result>');
    }

    public function testQuote()
    {
        Phake::when($this->connection)
            ->quote(Phake::anyParameters())
            ->thenReturn('"foo"');

        $result = $this->proxy->quote('foo', PDO::PARAM_STR);

        Phake::verify($this->connection)->quote('foo', PDO::PARAM_STR);

        $this->assertSame($result, '"foo"');
    }

    public function testLastInsertId()
    {
        Phake::when($this->connection)
            ->lastInsertId(Phake::anyParameters())
            ->thenReturn(123);

        $result = $this->proxy->lastInsertId('foo');

        Phake::verify($this->connection)->lastInsertId('foo');

        $this->assertSame($result, 123);
    }

    public function testBeginTransaction()
    {
        $this->proxy->beginTransaction();

        Phake::verify($this->connection)->beginTransaction();
    }

    public function testCommit()
    {
        $this->proxy->commit();

        Phake::verify($this->connection)->commit();
    }

    public function testRollBack()
    {
        $this->proxy->rollBack();

        Phake::verify($this->connection)->rollBack();
    }

    public function testInTransaction()
    {
        Phake::when($this->connection)
            ->inTransaction(Phake::anyParameters())
            ->thenReturn(true);

        $result = $this->proxy->inTransaction();

        Phake::verify($this->connection)->inTransaction();

        $this->assertTrue($result);
    }

    public function testErrorCode()
    {
        Phake::when($this->connection)
            ->errorCode(Phake::anyParameters())
            ->thenReturn(123);

        $result = $this->proxy->errorCode();

        Phake::verify($this->connection)->errorCode();

        $this->assertSame($result, 123);
    }

    public function testErrorInfo()
    {
        Phake::when($this->connection)
            ->errorInfo(Phake::anyParameters())
            ->thenReturn(array(1, 2, 3));

        $result = $this->proxy->errorInfo();

        Phake::verify($this->connection)->errorInfo();

        $this->assertSame($result, array(1, 2, 3));
    }

    public function testGetAttribute()
    {
        Phake::when($this->connection)
            ->getAttribute(Phake::anyParameters())
            ->thenReturn('foo');

        $result = $this->proxy->getAttribute(123);

        Phake::verify($this->connection)->getAttribute(123);

        $this->assertSame($result, 'foo');
    }

    public function testSetAttribute()
    {
        $this->proxy->setAttribute(123, 'foo');

        Phake::verify($this->connection)->setAttribute(123, 'foo');
    }
}
