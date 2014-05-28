<?php
namespace Icecave\Manifold\Connection\Facade;

use Eloquent\Liberator\Liberator;
use Icecave\Manifold\Replication\Exception\NoConnectionAvailableException;
use Icecave\Manifold\Replication\Exception\UnsupportedQueryException;
use Icecave\Manifold\Replication\SelectionStrategy\AnyStrategy;
use PDO;
use PDOException;
use PHPUnit_Framework_TestCase;
use Phake;

class ConnectionFacadeTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->queryConnectionSelector = Phake::mock('Icecave\Manifold\Replication\QueryConnectionSelectorInterface');
        $this->attributes = array(PDO::ATTR_TIMEOUT => 'foo', PDO::ATTR_CURSOR => 'bar');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->logger->id = 'default';
        $this->facade = new ConnectionFacade($this->queryConnectionSelector, $this->attributes, $this->logger);

        $this->connectionSelector = Phake::mock('Icecave\Manifold\Replication\ConnectionSelectorInterface');

        $this->defaultWriteStrategy = Phake::mock(
            'Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface'
        );
        Phake::when($this->defaultWriteStrategy)->string()->thenReturn('defaultWriteStrategy');
        $this->defaultReadStrategy = Phake::mock(
            'Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface'
        );
        Phake::when($this->defaultReadStrategy)->string()->thenReturn('defaultReadStrategy');
        $this->strategy = Phake::mock('Icecave\Manifold\Replication\SelectionStrategy\SelectionStrategyInterface');
        Phake::when($this->strategy)->string()->thenReturn('strategy');

        $this->connectionA = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionA)->name()->thenReturn('A');
        $this->connectionB = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionB)->name()->thenReturn('B');
        $this->connectionC = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionC)->name()->thenReturn('C');
        $this->connectionD = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->connectionD)->name()->thenReturn('D');

        $this->statement = Phake::mock('PDOStatement');

        Phake::when($this->queryConnectionSelector)->selector()->thenReturn($this->connectionSelector);
        Phake::when($this->connectionSelector)->defaultWriteStrategy()->thenReturn($this->defaultWriteStrategy);
        Phake::when($this->connectionSelector)->defaultReadStrategy()->thenReturn($this->defaultReadStrategy);

        Phake::when($this->connectionA)->setAttribute(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->connectionB)->setAttribute(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->connectionC)->setAttribute(Phake::anyParameters())->thenReturn(true);
        Phake::when($this->connectionD)->setAttribute(Phake::anyParameters())->thenReturn(true);

        Phake::when($this->connectionA)->beginTransaction()->thenReturn(true);
        Phake::when($this->connectionB)->beginTransaction()->thenReturn(true);
        Phake::when($this->connectionC)->beginTransaction()->thenReturn(true);
        Phake::when($this->connectionD)->beginTransaction()->thenReturn(true);

        Phake::when($this->connectionA)->commit()->thenReturn(true);
        Phake::when($this->connectionB)->commit()->thenReturn(true);
        Phake::when($this->connectionC)->commit()->thenReturn(true);
        Phake::when($this->connectionD)->commit()->thenReturn(true);

        Phake::when($this->connectionA)->rollBack()->thenReturn(true);
        Phake::when($this->connectionB)->rollBack()->thenReturn(true);
        Phake::when($this->connectionC)->rollBack()->thenReturn(true);
        Phake::when($this->connectionD)->rollBack()->thenReturn(true);

    }

    public function testConstructor()
    {
        $this->assertSame($this->queryConnectionSelector, $this->facade->queryConnectionSelector());
        $this->assertSame($this->attributes, $this->facade->attributes());
        $this->assertSame($this->logger, $this->facade->logger());
    }

    public function testConstructorDefaults()
    {
        $this->facade = new ConnectionFacade($this->queryConnectionSelector);

        $this->assertSame(array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION), $this->facade->attributes());
        $this->assertNull($this->facade->logger());
    }

    public function testConnectionSelector()
    {
        $this->assertSame($this->connectionSelector, $this->facade->connectionSelector());
    }

    public function testSetLogger()
    {
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->facade->setLogger($this->logger);

        $this->assertSame($this->logger, $this->facade->logger());
        Phake::verify($this->connectionSelector)->setLogger($this->logger);
    }

    // Implementation of ConnectionFacadeInterface =============================

    public function testSetDefaultWriteStrategy()
    {
        $this->facade->setDefaultWriteStrategy($this->strategy);

        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Setting default write strategy to {strategy}.',
                array('strategy' => 'strategy')
            ),
            Phake::verify($this->connectionSelector)->setDefaultWriteStrategy($this->strategy)
        );
    }

    public function testDefaultWriteStrategy()
    {
        $this->assertSame($this->defaultWriteStrategy, $this->facade->defaultWriteStrategy());
    }

    public function testSetDefaultReadStrategy()
    {
        $this->facade->setDefaultReadStrategy($this->strategy);

        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Setting default read strategy to {strategy}.',
                array('strategy' => 'strategy')
            ),
            Phake::verify($this->connectionSelector)->setDefaultReadStrategy($this->strategy)
        );
    }

    public function testDefaultReadStrategy()
    {
        $this->assertSame($this->defaultReadStrategy, $this->facade->defaultReadStrategy());
    }

    public function testPrepareWithStrategy()
    {
        $query = 'SELECT * FROM foo.bar';
        $attributes = array('baz' => 'qux');
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->prepare($query, $attributes)->thenReturn($this->statement);

        $this->assertSame(
            $this->statement,
            $this->facade->prepareWithStrategy($this->strategy, $query, $attributes)
        );
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Preparing statement {statement} with strategy {strategy}.',
                array('statement' => $query, 'strategy' => 'strategy')
            ),
            Phake::verify($this->queryConnectionSelector)->select($query, $this->strategy),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testPrepareWithStrategyCommentStack()
    {
        $this->facade->pushComment('comment');
        $query = 'SELECT * FROM foo.bar';
        $encapsulatedQuery = '/* comment */ SELECT * FROM foo.bar';
        $attributes = array('baz' => 'qux');
        Phake::when($this->queryConnectionSelector)->select($encapsulatedQuery, $this->strategy)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->prepare($encapsulatedQuery, $attributes)->thenReturn($this->statement);

        $this->assertSame(
            $this->statement,
            $this->facade->prepareWithStrategy($this->strategy, $query, $attributes)
        );
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Preparing statement {statement} with strategy {strategy}.',
                array('statement' => $encapsulatedQuery, 'strategy' => 'strategy')
            ),
            Phake::verify($this->queryConnectionSelector)->select($encapsulatedQuery, $this->strategy),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testPrepareWithStrategyDefaultOptions()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->prepare($query, array())->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->prepareWithStrategy($this->strategy, $query));
    }

    public function testQueryWithStrategy()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->query($query, 'one', 'two', 'three')->thenReturn($this->statement);

        $this->assertSame(
            $this->statement,
            $this->facade->queryWithStrategy($this->strategy, $query, 'one', 'two', 'three')
        );
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with strategy {strategy}.',
                array('statement' => $query, 'strategy' => 'strategy')
            ),
            Phake::verify($this->queryConnectionSelector)->select($query, $this->strategy),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testQueryWithStrategyCommentStack()
    {
        $this->facade->pushComment('comment');
        $query = 'SELECT * FROM foo.bar';
        $encapsulatedQuery = '/* comment */ SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($encapsulatedQuery, $this->strategy)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->query($encapsulatedQuery, 'one', 'two', 'three')->thenReturn($this->statement);

        $this->assertSame(
            $this->statement,
            $this->facade->queryWithStrategy($this->strategy, $query, 'one', 'two', 'three')
        );
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with strategy {strategy}.',
                array('statement' => $encapsulatedQuery, 'strategy' => 'strategy')
            ),
            Phake::verify($this->queryConnectionSelector)->select($encapsulatedQuery, $this->strategy),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testExecWithStrategy()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, $this->strategy)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);

        $this->assertSame(111, $this->facade->execWithStrategy($this->strategy, $query));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with strategy {strategy}.',
                array('statement' => $query, 'strategy' => 'strategy')
            ),
            Phake::verify($this->queryConnectionSelector)->select($query, $this->strategy),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testExecWithStrategyCommentStack()
    {
        $this->facade->pushComment('comment');
        $query = 'SELECT * FROM foo.bar';
        $encapsulatedQuery = '/* comment */ SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($encapsulatedQuery, $this->strategy)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($encapsulatedQuery)->thenReturn(111);

        $this->assertSame(111, $this->facade->execWithStrategy($this->strategy, $query));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with strategy {strategy}.',
                array('statement' => $encapsulatedQuery, 'strategy' => 'strategy')
            ),
            Phake::verify($this->queryConnectionSelector)->select($encapsulatedQuery, $this->strategy),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testPushPopComment()
    {
        $this->assertNull($this->facade->popComment());

        $this->facade->pushComment('foo');
        $this->facade->pushComment('bar %s', 'baz');

        $this->assertSame('bar baz', $this->facade->popComment());
        $this->assertSame('foo', $this->facade->popComment());
        $this->assertNull($this->facade->popComment());
    }

    // Implementation of PdoConnectionInterface ================================

    public function testPrepare()
    {
        $query = 'SELECT * FROM foo.bar';
        $attributes = array('baz' => 'qux');
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->prepare($query, $attributes)->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->prepare($query, $attributes));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Preparing statement {statement} with default strategy.',
                array('statement' => $query)
            ),
            Phake::verify($this->queryConnectionSelector)->select($query, null),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testPrepareCommentStack()
    {
        $this->facade->pushComment('comment');
        $query = 'SELECT * FROM foo.bar';
        $encapsulatedQuery = '/* comment */ SELECT * FROM foo.bar';
        $attributes = array('baz' => 'qux');
        Phake::when($this->queryConnectionSelector)->select($encapsulatedQuery, null)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->prepare($encapsulatedQuery, $attributes)->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->prepare($query, $attributes));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Preparing statement {statement} with default strategy.',
                array('statement' => $encapsulatedQuery)
            ),
            Phake::verify($this->queryConnectionSelector)->select($encapsulatedQuery, null),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testPrepareDefaultOptions()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->prepare($query, array())->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->prepare($query));
    }

    public function testQuery()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->query($query, 'one', 'two', 'three')->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->query($query, 'one', 'two', 'three'));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with default strategy.',
                array('statement' => $query)
            ),
            Phake::verify($this->queryConnectionSelector)->select($query, null),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testQueryCommentStack()
    {
        $this->facade->pushComment('comment');
        $query = 'SELECT * FROM foo.bar';
        $encapsulatedQuery = '/* comment */ SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($encapsulatedQuery, null)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->query($encapsulatedQuery, 'one', 'two', 'three')->thenReturn($this->statement);

        $this->assertSame($this->statement, $this->facade->query($query, 'one', 'two', 'three'));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with default strategy.',
                array('statement' => $encapsulatedQuery)
            ),
            Phake::verify($this->queryConnectionSelector)->select($encapsulatedQuery, null),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testQueryFailureNoStatement()
    {
        $this->setExpectedException('InvalidArgumentException', 'PDO::query() expects at least 1 parameter, 0 given');
        $this->facade->query();
    }

    public function testExec()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);

        $this->assertSame(111, $this->facade->exec($query));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with default strategy.',
                array('statement' => $query)
            ),
            Phake::verify($this->queryConnectionSelector)->select($query, null),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testExecCommentStack()
    {
        $this->facade->pushComment('comment');
        $query = 'SELECT * FROM foo.bar';
        $encapsulatedQuery = '/* comment */ SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($encapsulatedQuery, null)
            ->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($encapsulatedQuery)->thenReturn(111);

        $this->assertSame(111, $this->facade->exec($query));
        Phake::inOrder(
            Phake::verify($this->logger)->debug(
                'Executing statement {statement} with default strategy.',
                array('statement' => $encapsulatedQuery)
            ),
            Phake::verify($this->queryConnectionSelector)->select($encapsulatedQuery, null),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            )
        );
    }

    public function testBeginTransaction()
    {
        $this->assertTrue($this->facade->beginTransaction());
        $this->assertTrue($this->facade->inTransaction());
        Phake::verify($this->logger)->debug('Beginning transaction on facade.');
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
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
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
            Phake::verify($this->logger)->debug('Committing transaction on facade.'),
            Phake::verify($this->connectionA)->commit(),
            Phake::verify($this->connectionB)->commit()
        );
    }

    public function testCommitFailure()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
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
            Phake::verify($this->logger)->debug('Committing transaction on facade.'),
            Phake::verify($this->connectionA)->commit(),
            Phake::verify($this->connectionB)->commit()
        );
    }

    public function testCommitError()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
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
            Phake::verify($this->logger)->debug('Committing transaction on facade.'),
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
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
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
            Phake::verify($this->logger)->debug('Rolling back transaction on facade.'),
            Phake::verify($this->connectionA)->rollBack(),
            Phake::verify($this->connectionB)->rollBack()
        );
    }

    public function testRollBackFailure()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
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
            Phake::verify($this->logger)->debug('Rolling back transaction on facade.'),
            Phake::verify($this->connectionA)->rollBack(),
            Phake::verify($this->connectionB)->rollBack()
        );
    }

    public function testRollBackError()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
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
            Phake::verify($this->logger)->debug('Rolling back transaction on facade.'),
            Phake::verify($this->connectionA)->rollBack(),
            Phake::verify($this->connectionB)->rollBack()
        );
    }

    public function testRollBackFailureNoTransaction()
    {
        $this->setExpectedException('PDOException', 'There is no active transaction');
        $this->facade->rollBack();
    }

    public function testLastInsertId()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);
        Phake::when($this->connectionA)->lastInsertId(null)->thenReturn('222');
        Phake::when($this->connectionA)->lastInsertId('foo')->thenReturn('333');
        $this->facade->exec($query);

        $this->assertSame('222', $this->facade->lastInsertId());
        $this->assertSame('333', $this->facade->lastInsertId('foo'));
    }

    public function testLastInsertIdNoConnection()
    {
        $this->assertSame('0', $this->facade->lastInsertId());
    }

    public function testErrorCode()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);
        Phake::when($this->connectionA)->errorCode()->thenReturn('22222');
        $this->facade->exec($query);

        $this->assertSame('22222', $this->facade->errorCode());
    }

    public function testErrorCodeNoConnection()
    {
        $this->assertNull($this->facade->errorCode());
    }

    public function testErrorInfo()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);
        Phake::when($this->connectionA)->errorInfo()->thenReturn(array('22222', 333, 'message'));
        $this->facade->exec($query);

        $this->assertSame(array('22222', 333, 'message'), $this->facade->errorInfo());
    }

    public function testErrorInfoNoConnection()
    {
        $this->assertSame(array('', null, null), $this->facade->errorInfo());
    }

    public function testQuote()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);
        Phake::when($this->connectionA)->quote(111, PDO::PARAM_INT)->thenReturn('111');
        $this->facade->exec($query);

        $this->assertSame('111', $this->facade->quote(111, PDO::PARAM_INT));
    }

    public function testQuoteNoConnection()
    {
        Phake::when($this->connectionSelector)->forRead(null, new AnyStrategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->quote(111, PDO::PARAM_INT)->thenReturn('111');

        $this->assertSame('111', $this->facade->quote(111, PDO::PARAM_INT));
    }

    public function testQuoteDefaults()
    {
        Phake::when($this->connectionSelector)->forRead(null, new AnyStrategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->quote('foo', PDO::PARAM_STR)->thenReturn('foo');

        $this->assertSame('foo', $this->facade->quote('foo'));
    }

    public function testSetAttribute()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionA)->exec($queryB)->thenReturn(222);
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);

        $this->assertTrue($this->facade->setAttribute(PDO::ATTR_PREFETCH, 'baz'));
        $log = Phake::verify($this->logger)->debug(
            'Setting attribute {attribute} to {value} on facade.',
            array('attribute' => 'PDO::ATTR_PREFETCH', 'value' => 'baz')
        );
        $setA = Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        $setB = Phake::verify($this->connectionB)->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        Phake::inOrder($log, $setA);
        Phake::inOrder($log, $setB);
    }

    public function testSetAttributeFailure()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionA)->exec($queryB)->thenReturn(222);
        Phake::when($this->connectionA)->setAttribute(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(true)
            ->thenReturn(false);
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);

        $this->assertFalse($this->facade->setAttribute(PDO::ATTR_PREFETCH, 'baz'));
        $log = Phake::verify($this->logger)->debug(
            'Setting attribute {attribute} to {value} on facade.',
            array('attribute' => 'PDO::ATTR_PREFETCH', 'value' => 'baz')
        );
        $setA = Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        $setB = Phake::verify($this->connectionB)->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        Phake::inOrder($log, $setA);
        Phake::inOrder($log, $setB);
    }

    public function testSetAttributeException()
    {
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, false)
        );
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionA)->exec($queryB)->thenReturn(222);
        $error = new Exception\PdoException('Unable to set attribute.');
        Phake::when($this->connectionA)->setAttribute(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(true)
            ->thenThrow($error);
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);

        $thrown = null;
        try {
            $this->facade->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        } catch (PDOException $thrown) {}
        $this->assertSame($error, $thrown);
        $log = Phake::verify($this->logger)->debug(
            'Setting attribute {attribute} to {value} on facade.',
            array('attribute' => 'PDO::ATTR_PREFETCH', 'value' => 'baz')
        );
        $setA = Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        $setB = Phake::verify($this->connectionB)->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        Phake::inOrder($log, $setA);
        Phake::inOrder($log, $setB);
    }

    public function testGetAttribute()
    {
        $query = 'SELECT * FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->exec($query)->thenReturn(111);
        Phake::when($this->connectionA)->getAttribute(222)->thenReturn('foo');
        $this->facade->exec($query);

        $this->assertSame('foo', $this->facade->getAttribute(222));
    }

    public function testGetAttributeNoConnection()
    {
        Phake::when($this->connectionSelector)->forRead(null, new AnyStrategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionA)->getAttribute(222)->thenReturn('foo');

        $this->assertSame('foo', $this->facade->getAttribute(222));
    }

    public function testSelectConnectionForWrite()
    {
        Phake::when($this->connectionSelector)->forWrite('foo', $this->strategy)->thenReturn($this->connectionA);

        $this->assertSame(
            $this->connectionA,
            Liberator::liberate($this->facade)->selectConnectionForWrite('foo', $this->strategy)
        );
        Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_TIMEOUT, 'foo');
        Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_CURSOR, 'bar');
    }

    public function testSelectConnectionForWriteDefaults()
    {
        Phake::when($this->connectionSelector)->forWrite(null, null)->thenReturn($this->connectionA);

        $this->assertSame($this->connectionA, Liberator::liberate($this->facade)->selectConnectionForWrite());
        Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_TIMEOUT, 'foo');
        Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_CURSOR, 'bar');
    }

    public function testSelectConnectionForWriteFailureNoConnection()
    {
        Phake::when($this->connectionSelector)->forWrite(null, null)->thenThrow(new NoConnectionAvailableException);

        $this->setExpectedException('PDOException', 'No suitable connection available.');
        Liberator::liberate($this->facade)->selectConnectionForWrite();
    }

    // Functional tests ========================================================

    public function testConnectionActivation()
    {
        $logger = Phake::mock('Psr\Log\LoggerInterface');
        $logger->id = 'custom';
        $queryA = 'SELECT a FROM foo.bar';
        $queryB = 'SELECT b FROM foo.bar';
        $queryC = 'SELECT c FROM foo.bar';
        $queryD = 'SELECT d FROM foo.bar';
        $queryE = 'SELECT e FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryC, null)->thenReturn(
            array($this->connectionB, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryD, null)->thenReturn(
            array($this->connectionC, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryE, null)->thenReturn(
            array($this->connectionD, false)
        );
        Phake::when($this->connectionA)->exec($queryA)->thenReturn(111);
        Phake::when($this->connectionA)->exec($queryB)->thenReturn(222);
        Phake::when($this->connectionB)->exec($queryC)->thenReturn(333);
        Phake::when($this->connectionC)->exec($queryD)->thenReturn(444);
        Phake::when($this->connectionD)->exec($queryE)->thenReturn(555);
        $this->facade->exec($queryA);
        $this->facade->setAttribute(PDO::ATTR_PREFETCH, 'baz');
        $this->facade->setLogger($logger);
        $this->facade->exec($queryB);
        $this->facade->beginTransaction();
        $this->facade->exec($queryC);
        $this->facade->exec($queryD);
        $this->facade->commit();
        $this->facade->exec($queryE);

        Phake::inOrder(
            Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_TIMEOUT, 'foo'),
            Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_CURSOR, 'bar'),
            Phake::verify($this->connectionA)->setLogger($this->logger),
            Phake::verify($this->logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            ),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionA)->setAttribute(PDO::ATTR_PREFETCH, 'baz'),
            Phake::verify($this->connectionA)->setLogger($logger),
            Phake::verify($logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'A')
            ),
            Phake::verify($this->connectionA)->exec($queryB),

            Phake::verify($this->connectionB)->setAttribute(PDO::ATTR_TIMEOUT, 'foo'),
            Phake::verify($this->connectionB)->setAttribute(PDO::ATTR_CURSOR, 'bar'),
            Phake::verify($this->connectionB)->setAttribute(PDO::ATTR_PREFETCH, 'baz'),
            Phake::verify($this->connectionB)->setLogger($logger),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'B')
            ),
            Phake::verify($this->connectionB)->exec($queryC),

            Phake::verify($this->connectionC)->setAttribute(PDO::ATTR_TIMEOUT, 'foo'),
            Phake::verify($this->connectionC)->setAttribute(PDO::ATTR_CURSOR, 'bar'),
            Phake::verify($this->connectionC)->setAttribute(PDO::ATTR_PREFETCH, 'baz'),
            Phake::verify($this->connectionC)->setLogger($logger),
            Phake::verify($this->connectionC)->beginTransaction(),
            Phake::verify($logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'C')
            ),
            Phake::verify($this->connectionC)->exec($queryD),

            Phake::verify($this->connectionB)->commit(),
            Phake::verify($this->connectionC)->commit(),

            Phake::verify($this->connectionD)->setAttribute(PDO::ATTR_TIMEOUT, 'foo'),
            Phake::verify($this->connectionD)->setAttribute(PDO::ATTR_CURSOR, 'bar'),
            Phake::verify($this->connectionD)->setAttribute(PDO::ATTR_PREFETCH, 'baz'),
            Phake::verify($this->connectionD)->setLogger($logger),
            Phake::verify($logger)->debug(
                'Setting current connection to {connection}.',
                array('connection' => 'D')
            ),
            Phake::verify($this->connectionD)->exec($queryE)
        );
        Phake::verify($this->connectionA, Phake::never())->beginTransaction();
        Phake::verify($this->connectionD, Phake::never())->beginTransaction();
        Phake::verify($this->connectionA, Phake::never())->commit();
        Phake::verify($this->connectionD, Phake::never())->commit();
    }

    public function testConnectionActivationFailureAttribute()
    {
        $query = 'SELECT a FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->setAttribute(Phake::anyParameters())->thenReturn(false);

        $this->setExpectedException('PDOException', 'Unable to set attribute on child connection.');
        $this->facade->exec($query);
    }

    public function testConnectionActivationFailureTransaction()
    {
        $query = 'SELECT a FROM foo.bar';
        Phake::when($this->queryConnectionSelector)->select($query, null)->thenReturn(array($this->connectionA, false));
        Phake::when($this->connectionA)->beginTransaction(Phake::anyParameters())->thenReturn(false);
        $this->facade->beginTransaction();

        $this->setExpectedException('PDOException', 'Unable to begin transaction on child connection.');
        $this->facade->exec($query);
    }

    public function testQueryConnectionSelectionFailureUnsupportedQuery()
    {
        $query = 'SHOW MEANING OF LIFE';
        Phake::when($this->queryConnectionSelector)->select(Phake::anyParameters())
            ->thenThrow(new UnsupportedQueryException($query));

        $this->setExpectedException('PDOException', "Unsupported query 'SHOW MEANING OF LIFE'.");
        $this->facade->exec($query);
    }

    public function testQueryConnectionSelectionFailureNoConnection()
    {
        Phake::when($this->queryConnectionSelector)->select(Phake::anyParameters())
            ->thenThrow(new NoConnectionAvailableException);

        $this->setExpectedException('PDOException', 'No suitable connection available.');
        $this->facade->exec('SELECT a FROM foo.bar');
    }

    public function testConnectionSelectionForReadFailureNoConnection()
    {
        Phake::when($this->connectionSelector)->forRead(null, new AnyStrategy)
            ->thenThrow(new NoConnectionAvailableException);

        $this->setExpectedException('PDOException', 'No suitable connection available.');
        $this->facade->quote('foo');
    }

    public function testTransactionWriteConnection()
    {
        $queryA = 'SELECT a FROM a.a';
        $queryB = 'DELETE FROM b.b';
        $queryC = 'SELECT c FROM c.c';
        $queryD = 'DELETE FROM d.d';
        $queryE = 'SELECT e FROM e.e';
        Phake::when($this->queryConnectionSelector)->select($queryA, null)->thenReturn(
            array($this->connectionA, false)
        );
        Phake::when($this->queryConnectionSelector)->select($queryB, null)->thenReturn(
            array($this->connectionB, true)
        );
        Phake::when($this->queryConnectionSelector)->select($queryE, null)->thenReturn(
            array($this->connectionC, false)
        );
        $this->facade->beginTransaction();
        $this->facade->exec($queryA);
        $this->facade->exec($queryB);
        $this->facade->exec($queryC);
        $this->facade->exec($queryD);
        $this->facade->commit();
        $this->facade->exec($queryE);

        $transactionWriteLog = Phake::verify($this->logger, Phake::times(2))->debug(
            'Connection {connection} selected because an existing write transaction is in effect.',
            array('connection' => 'B')
        );
        Phake::inOrder(
            Phake::verify($this->connectionA)->beginTransaction(),
            Phake::verify($this->connectionA)->exec($queryA),
            Phake::verify($this->connectionB)->beginTransaction(),
            Phake::verify($this->connectionB)->exec($queryB),
            $transactionWriteLog,
            Phake::verify($this->connectionB)->exec($queryC),
            $transactionWriteLog,
            Phake::verify($this->connectionB)->exec($queryD),
            Phake::verify($this->connectionA)->commit(),
            Phake::verify($this->connectionB)->commit(),
            Phake::verify($this->connectionC)->exec($queryE)
        );
        Phake::when($this->queryConnectionSelector, Phake::never())->select($queryC, null);
        Phake::when($this->queryConnectionSelector, Phake::never())->select($queryD, null);
    }

    public function testTransactionWriteConnectionNonStatementSelection()
    {
        Phake::when($this->connectionSelector)->forWrite('foo', $this->strategy)->thenReturn($this->connectionA);
        Phake::when($this->connectionSelector)->forRead('foo', $this->strategy)->thenReturn($this->connectionB);
        $this->facade->beginTransaction();

        $this->assertSame(
            $this->connectionA,
            Liberator::liberate($this->facade)->selectConnectionForWrite('foo', $this->strategy)
        );
        $this->assertSame($this->connectionA, Liberator::liberate($this->facade)->selectConnectionForWrite());
        $this->assertSame($this->connectionA, Liberator::liberate($this->facade)->selectConnectionForRead());
        $this->assertSame($this->connectionA, Liberator::liberate($this->facade)->selectConnectionForRead());

        $this->facade->rollBack();

        $this->assertSame(
            $this->connectionB,
            Liberator::liberate($this->facade)->selectConnectionForRead('foo', $this->strategy)
        );
    }
}
