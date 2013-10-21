<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Isolator\Isolator;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Connection\LazyPdoConnection;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use Icecave\Manifold\Connection\Pool\ConnectionPoolSelector;
use Icecave\Manifold\Connection\Pool\ReadWritePair;
use Icecave\Manifold\Replication\ReplicationTree;
use Icecave\Parity\Parity;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Icecave\Manifold\Configuration\ConfigurationReader
 * @covers \Icecave\Manifold\Configuration\EnvironmentVariableTransform
 */
class ConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock(Isolator::className());
        $this->innerReader = Phake::mock('Eloquent\Schemer\Reader\ReaderInterface');
        $this->environmentVariableTransform = new EnvironmentVariableTransform($this->isolator);
        $this->connectionFactory = new ConnectionFactory;

        $this->reader = new ConfigurationReader(null, $this->environmentVariableTransform, $this->connectionFactory);

        $this->fixturePath = __DIR__ . '/../../../../fixture/config';
    }

    public function testConstructor()
    {
        $this->reader = new ConfigurationReader(
            $this->innerReader,
            $this->environmentVariableTransform,
            $this->connectionFactory
        );

        $this->assertSame($this->innerReader, $this->reader->reader());
        $this->assertSame($this->environmentVariableTransform, $this->reader->environmentVariableTransform());
        $this->assertSame($this->connectionFactory, $this->reader->connectionFactory());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new ConfigurationReader;

        $this->assertInstanceOf('Eloquent\Schemer\Reader\ValidatingReader', $this->reader->reader());
        $this->assertInstanceOf(
            __NAMESPACE__ . '\EnvironmentVariableTransform',
            $this->reader->environmentVariableTransform()
        );
        $this->assertInstanceOf('Icecave\Manifold\Connection\ConnectionFactory', $this->reader->connectionFactory());
    }

    public function testConfigurationFromString()
    {
        $string = <<<'EOD'
connections:
    foo:
        dsn: mysql:host=foo
EOD;
        $configuration = $this->reader->readString($string);
        $expectedConnections = new Map(
            array(
                'foo' => new LazyPdoConnection('mysql:host=foo'),
            )
        );
        $expectedPools = new Map;
        $expectedPool = new ConnectionPool(new Vector(array($expectedConnections->get('foo'))));
        $expectedSelector = new ConnectionPoolSelector(
            new ReadWritePair($expectedPool, $expectedPool)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections->get('foo'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTree));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionPoolSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function testMinimalConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-minimal.yml');
        $expectedConnections = new Map(
            array(
                'foo' => new LazyPdoConnection('mysql:host=foo'),
            )
        );
        $expectedPools = new Map;
        $expectedPool = new ConnectionPool(new Vector(array($expectedConnections->get('foo'))));
        $expectedSelector = new ConnectionPoolSelector(
            new ReadWritePair($expectedPool, $expectedPool)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections->get('foo'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTree));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionPoolSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function testFullConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-full.yml');
        $expectedConnections = new Map(
            array(
                'master1' => new LazyPdoConnection('mysql:host=master1', 'username', 'password'),
                'master2' => new LazyPdoConnection('mysql:host=master2'),
                'master3' => new LazyPdoConnection('mysql:host=master3'),
                'reporting1' => new LazyPdoConnection('mysql:host=reporting1'),
                'slave101' => new LazyPdoConnection('mysql:host=slave101'),
                'slave102' => new LazyPdoConnection('mysql:host=slave102'),
                'reporting2' => new LazyPdoConnection('mysql:host=reporting2'),
                'slave201' => new LazyPdoConnection('mysql:host=slave201'),
                'slave202' => new LazyPdoConnection('mysql:host=slave202'),
                'reporting3' => new LazyPdoConnection('mysql:host=reporting3'),
                'slave301' => new LazyPdoConnection('mysql:host=slave301'),
                'slave302' => new LazyPdoConnection('mysql:host=slave302'),
            )
        );
        $expectedPools = new Map(
            array(
                'pool1' => new ConnectionPool(
                    new Vector(
                        array(
                            $expectedConnections->get('slave101'),
                            $expectedConnections->get('slave102'),
                        )
                    )
                ),
                'pool2' => new ConnectionPool(
                    new Vector(
                        array(
                            $expectedConnections->get('slave201'),
                            $expectedConnections->get('slave202'),
                        )
                    )
                ),
            )
        );
        $expectedSelector = new ConnectionPoolSelector(
            new ReadWritePair(
                new ConnectionPool(new Vector(array($expectedConnections->get('reporting1')))),
                $expectedPools->get('pool1')
            ),
            new Map(
                array(
                    'app_data' => new ReadWritePair(
                        new ConnectionPool(new Vector(array($expectedConnections->get('master1')))),
                        $expectedPools->get('pool1')
                    ),
                    'app_reporting' => new ReadWritePair(
                        new ConnectionPool(new Vector(array($expectedConnections->get('reporting2')))),
                        $expectedPools->get('pool2')
                    ),
                    'app_temp' => new ReadWritePair(
                        $expectedPools->get('pool2'),
                        $expectedPools->get('pool2')
                    ),
                    'app_read_only' => new ReadWritePair(
                        null,
                        new ConnectionPool(new Vector(array($expectedConnections->get('master2'))))
                    ),
                    'app_write_only' => new ReadWritePair(
                        new ConnectionPool(new Vector(array($expectedConnections->get('master2'))))
                    ),
                )
            )
        );
        $expectedReplicationTreeA = new ReplicationTree($expectedConnections->get('master1'));
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('master1'),
            $expectedConnections->get('master2')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('master1'),
            $expectedConnections->get('reporting1')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('reporting1'),
            $expectedConnections->get('slave101')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('reporting1'),
            $expectedConnections->get('slave102')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('master1'),
            $expectedConnections->get('reporting2')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('reporting2'),
            $expectedConnections->get('slave201')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('reporting2'),
            $expectedConnections->get('slave202')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('master1'),
            $expectedConnections->get('reporting3')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('reporting3'),
            $expectedConnections->get('slave301')
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections->get('reporting3'),
            $expectedConnections->get('slave302')
        );
        $expectedReplicationTreeB = new ReplicationTree($expectedConnections->get('master3'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTreeA, $expectedReplicationTreeB));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionPoolSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function testConfigurationEnvironmentVariables()
    {
        $string = <<<'EOD'
connections:
    foo:
        dsn: mysql:host=foo
        username: $USERNAME
        password: \$ESCAPED
    bar:
        dsn: mysql:host=bar
        username: \\$ESCAPED
        password: IG$NORED
EOD;
        Phake::when($this->isolator)->getenv('USERNAME')->thenReturn('username');
        $configuration = $this->reader->readString($string);
        $expectedConnections = new Map(
            array(
                'foo' => new LazyPdoConnection('mysql:host=foo', 'username', '$ESCAPED'),
                'bar' => new LazyPdoConnection('mysql:host=bar', '\\$ESCAPED', 'IG$NORED'),
            )
        );
        $expectedPools = new Map;
        $expectedPool = new ConnectionPool(new Vector(array($expectedConnections->get('foo'))));
        $expectedSelector = new ConnectionPoolSelector(
            new ReadWritePair($expectedPool, $expectedPool)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections->get('foo'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTree));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionPoolSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function invalidConfigurationData()
    {
        return array(
            'Empty'                => array('empty',                'Eloquent\Schemer\Validation\Exception\InvalidValueException'),
            'No connections'       => array('no-connections',       'Eloquent\Schemer\Validation\Exception\InvalidValueException'),
            'Undefined connection' => array('undefined-connection', __NAMESPACE__ . '\Exception\UndefinedConnectionException'),
            'Undefined pool'       => array('undefined-pool',       __NAMESPACE__ . '\Exception\UndefinedConnectionException'),
        );
    }

    /**
     * @dataProvider invalidConfigurationData
     */
    public function testInvalidConfiguration($fixtueName, $expected)
    {
        $fixturePath = sprintf('%s/invalid-%s.yml', $this->fixturePath, $fixtueName);

        $this->setExpectedException($expected);
        $this->reader->readFile($fixturePath);
    }

    public function testConfigurationEnvironmentVariablesFailure()
    {
        $string = <<<'EOD'
connections:
    foo:
        dsn: mysql:host=foo
        username: $USERNAME
EOD;
        Phake::when($this->isolator)->getenv('USERNAME')->thenReturn(false);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\UndefinedEnvironmentVariableException');
        $configuration = $this->reader->readString($string);
    }
}
