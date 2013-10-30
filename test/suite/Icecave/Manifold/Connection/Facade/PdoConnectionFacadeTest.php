<?php
namespace Icecave\Manifold\Connection\Facade;

use Icecave\Collections\Map;
use PDOException;
use PHPUnit_Framework_TestCase;
use Phake;

class PdoConnectionFacadeTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->queryConnectionSelector = Phake::mock('Icecave\Manifold\Replication\QueryConnectionSelectorInterface');
        $this->attributes = new Map(array('foo' => 'bar', 'baz' => 'qux'));
        $this->facade = new PdoConnectionFacade($this->queryConnectionSelector, $this->attributes);

        $this->connectionASelector = Phake::mock('Icecave\Manifold\Replication\ConnectionSelectorInterface');

        $this->defaultStrategy = Phake::mock(
            'Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface'
        );
        $this->strategy = Phake::mock('Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface');

        $this->connectionA = Phake::mock('PDO');
        $this->connectionA->id = 'A';
        $this->connectionB = Phake::mock('PDO');
        $this->connectionB->id = 'B';
        $this->connectionC = Phake::mock('PDO');
        $this->connectionC->id = 'C';

        $this->statement = Phake::mock('PDOStatement');

        Phake::when($this->queryConnectionSelector)->selector()->thenReturn($this->connectionASelector);
        Phake::when($this->connectionASelector)->defaultStrategy()->thenReturn($this->defaultStrategy);

        Phake::when($this->connectionA)->setAttribute(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->connectionB)->setAttribute(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->connectionC)->setAttribute(Phake::anyParameters())->thenReturn(true);

        Phake::when($this->connectionA)->beginTransaction()->thenReturn(true);
        Phake::when($this->connectionB)->beginTransaction()->thenReturn(true);
        Phake::when($this->connectionC)->beginTransaction()->thenReturn(true);

        Phake::when($this->connectionA)->commit()->thenReturn(true);
        Phake::when($this->connectionB)->commit()->thenReturn(true);
        Phake::when($this->connectionC)->commit()->thenReturn(true);

        Phake::when($this->connectionA)->rollBack()->thenReturn(true);
        Phake::when($this->connectionB)->rollBack()->thenReturn(true);
        Phake::when($this->connectionC)->rollBack()->thenReturn(true);
    }

    public function testConstructor()
    {
        $this->assertSame($this->queryConnectionSelector, $this->facade->queryConnectionSelector());
        $this->assertEquals($this->attributes, $this->facade->attributes());
        $this->assertNotSame($this->attributes, $this->facade->attributes());
    }

    public function testConstructorDefaults()
    {
        $this->facade = new PdoConnectionFacade($this->queryConnectionSelector);

        $this->assertEquals(new Map, $this->facade->attributes());
    }

    public function testConnectionSelector()
    {
        $this->assertSame($this->connectionASelector, $this->facade->connectionSelector());
    }

    // Implementation of PdoConnectionFacadeInterface ==========================

    public function testSetDefaultStrategy()
    {
        $this->facade->setDefaultStrategy($this->strategy);

        Phake::verify($this->connectionASelector)->setDefaultStrategy($this->strategy);
    }

    public function testDefaultStrategy()
    {
        $this->assertSame($this->defaultStrategy, $this->facade->defaultStrategy());
    }

    public function testPrepareWithStrategy()
    {
        $query = 'SELECT * FROM foo.bar';
        $driverOptions = array('baz' => 'qux');
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->prepare($query, $driverOptions)->thenReturn($this->statement);

        $this->assertSame(
            $this->statement,
            $this->facade->prepareWithStrategy($this->strategy, $query, $driverOptions)
        );
    }

    public function testPrepareWithStrategyDefaultOptions()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->prepare($query, array())->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->prepareWithStrategy($this->strategy, $query));
    }

    public function testQueryWithStrategy()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->query($query, 'one', 'two', 'three')->thenReturn($this->statement);

        $this->assertSame(
            $this->statement,
            $this->facade->queryWithStrategy($this->strategy, $query, 'one', 'two', 'three')
        );
    }

    public function testExecWithStrategy()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);

        $this->assertSame(111, $this->facade->execWithStrategy($this->strategy, $query));
    }

    // Implementation of PdoConnectionInterface ================================

    public function testPrepare()
    {
        $query = 'SELECT * FROM foo.bar';
        $driverOptions = array('baz' => 'qux');
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->prepare($query, $driverOptions)->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->prepare($query, $driverOptions));
    }

    public function testPrepareDefaultOptions()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->prepare($query, array())->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->prepare($query));
    }

    public function testQuery()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->query($query, 'one', 'two', 'three')->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->query($query, 'one', 'two', 'three'));
    }

    public function testQueryFailureNoStatement()
    {
        $this->setExpectedException('InvalidArgumentException', 'PDO::query() expects at least 1 parameter, 0 given');
        $this->facade->query();
    }

    public function testExec()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);

        $this->assertSame(111, $this->facade->exec($query));
    }

    public function testBeginTransaction()
    {
        $this->assertTrue($this->facade->beginTransaction());
        $this->assertTrue($this->facade->inTransaction());
    }

    public function testBeginTransactionFailure()
    {
        $this->facade->beginTransaction();

        $this->setExpectedException('PDOException', 'There is already an active transaction');
        $this->facade->beginTransaction();
    }

    public function testCommit()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn($this->connectionA);
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn($this->connectionB);
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionB)->exec($queryB)->thenReturn(222);
        $this->facade->beginTransaction();
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);

        $this->assertTrue($this->facade->commit());
        Phake::inOrder(
            Phake::verify($this->connectionA)->beginTransaction(),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($this->connectionB)->exec($queryB),
            Phake::verify($this->connectionA)->commit(),
            Phake::verify($this->connectionB)->commit()
        );
    }

    public function testCommitFailure()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn($this->connectionA);
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn($this->connectionB);
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionB)->exec($queryB)->thenReturn(222);
        $this->facade->beginTransaction();
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);
        Phake::when($this->connectionA)->commit()->thenReturn(false);

        $this->assertFalse($this->facade->commit());
        Phake::inOrder(
            Phake::verify($this->connectionA)->beginTransaction(),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($this->connectionB)->exec($queryB),
            Phake::verify($this->connectionA)->commit(),
            Phake::verify($this->connectionB)->commit()
        );
    }

    public function testCommitError()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn($this->connectionA);
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn($this->connectionB);
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionB)->exec($queryB)->thenReturn(222);
        $this->facade->beginTransaction();
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);
        $error = new Exception\PdoException('Unable to commit.');
        Phake::when($this->connectionA)->commit()->thenThrow($error);

        $thrown = null;
        try {
            $this->facade->commit();
        } catch (PDOException $thrown) {}
        $this->assertSame($error, $thrown);
        Phake::inOrder(
            Phake::verify($this->connectionA)->beginTransaction(),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($this->connectionB)->exec($queryB),
            Phake::verify($this->connectionA)->commit(),
            Phake::verify($this->connectionB)->commit()
        );
    }

    public function testCommitFailureNoTransaction()
    {
        $this->setExpectedException('PDOException', 'There is no active transaction');
        $this->facade->commit();
    }

    public function testRollBack()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn($this->connectionA);
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn($this->connectionB);
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionB)->exec($queryB)->thenReturn(222);
        $this->facade->beginTransaction();
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);

        $this->assertTrue($this->facade->rollBack());
        Phake::inOrder(
            Phake::verify($this->connectionA)->beginTransaction(),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($this->connectionB)->exec($queryB),
            Phake::verify($this->connectionA)->rollBack(),
            Phake::verify($this->connectionB)->rollBack()
        );
    }

    public function testRollBackFailure()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn($this->connectionA);
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn($this->connectionB);
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionB)->exec($queryB)->thenReturn(222);
        $this->facade->beginTransaction();
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);
        Phake::when($this->connectionA)->rollBack()->thenReturn(false);

        $this->assertFalse($this->facade->rollBack());
        Phake::inOrder(
            Phake::verify($this->connectionA)->beginTransaction(),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($this->connectionB)->exec($queryB),
            Phake::verify($this->connectionA)->rollBack(),
            Phake::verify($this->connectionB)->rollBack()
        );
    }

    public function testRollBackError()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn($this->connectionA);
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn($this->connectionB);
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionB)->exec($queryB)->thenReturn(222);
        $this->facade->beginTransaction();
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);
        $error = new Exception\PdoException('Unable to roll back.');
        Phake::when($this->connectionA)->rollBack()->thenThrow($error);

        $thrown = null;
        try {
            $this->facade->rollBack();
        } catch (PDOException $thrown) {}
        $this->assertSame($error, $thrown);
        Phake::inOrder(
            Phake::verify($this->connectionA)->beginTransaction(),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($this->connectionB)->exec($queryB),
            Phake::verify($this->connectionA)->rollBack(),
            Phake::verify($this->connectionB)->rollBack()
        );
    }

    public function testRollBackFailureNoTransaction()
    {
        $this->setExpectedException('PDOException', 'There is no active transaction');
        $this->facade->rollBack();
    }
}
