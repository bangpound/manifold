<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Isolator\Isolator;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Connection\LazyConnection;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use Icecave\Manifold\Connection\Pool\ConnectionPoolSelector;
use Icecave\Manifold\Connection\Pool\ConnectionPoolPair;
use Icecave\Manifold\Replication\ReplicationTree;
use Icecave\Parity\Parity;
use PHPUnit_Framework_TestCase;
use Phake;

class ConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock(Isolator::className());
        $this->innerReader = Phake::mock('Eloquent\Schemer\Reader\ReaderInterface');
        $this->connectionFactory = new ConnectionFactory;

        $this->reader = new ConfigurationReader(null, $this->connectionFactory);

        $this->fixturePath = __DIR__ . '/../../../../fixture/config';
    }

    public function testConstructor()
    {
        $this->reader = new ConfigurationReader($this->innerReader, $this->connectionFactory);

        $this->assertSame($this->innerReader, $this->reader->reader());
        $this->assertSame($this->connectionFactory, $this->reader->connectionFactory());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new ConfigurationReader;

        $this->assertInstanceOf('Eloquent\Schemer\Reader\ValidatingReader', $this->reader->reader());
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
                'foo' => new LazyConnection('foo', 'mysql:host=foo'),
            )
        );
        $expectedPools = new Map;
        $expectedPool = new ConnectionPool('foo', new Vector(array($expectedConnections->get('foo'))));
        $expectedSelector = new ConnectionPoolSelector(
            new ConnectionPoolPair($expectedPool, $expectedPool)
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
                'foo' => new LazyConnection('foo', 'mysql:host=foo'),
            )
        );
        $expectedPools = new Map;
        $expectedPool = new ConnectionPool('foo', new Vector(array($expectedConnections->get('foo'))));
        $expectedSelector = new ConnectionPoolSelector(
            new ConnectionPoolPair($expectedPool, $expectedPool)
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
        $configurationSplit = $this->reader->readFile($this->fixturePath . '/valid-full-split.yml');
        $expectedConnections = new Map(
            array(
                'master1' => new LazyConnection('master1', 'mysql:host=master1', 'username', 'password'),
                'master2' => new LazyConnection('master2', 'mysql:host=master2'),
                'master3' => new LazyConnection('master3', 'mysql:host=master3'),
                'reporting1' => new LazyConnection('reporting1', 'mysql:host=reporting1'),
                'slave101' => new LazyConnection('slave101', 'mysql:host=slave101'),
                'slave102' => new LazyConnection('slave102', 'mysql:host=slave102'),
                'reporting2' => new LazyConnection('reporting2', 'mysql:host=reporting2'),
                'slave201' => new LazyConnection('slave201', 'mysql:host=slave201'),
                'slave202' => new LazyConnection('slave202', 'mysql:host=slave202'),
                'reporting3' => new LazyConnection('reporting3', 'mysql:host=reporting3'),
                'slave301' => new LazyConnection('slave301', 'mysql:host=slave301'),
                'slave302' => new LazyConnection('slave302', 'mysql:host=slave302'),
            )
        );
        $expectedPools = new Map(
            array(
                'pool1' => new ConnectionPool(
                    'pool1',
                    new Vector(
                        array(
                            $expectedConnections->get('slave101'),
                            $expectedConnections->get('slave102'),
                        )
                    )
                ),
                'pool2' => new ConnectionPool(
                    'pool2',
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
            new ConnectionPoolPair(
                new ConnectionPool('reporting1', new Vector(array($expectedConnections->get('reporting1')))),
                $expectedPools->get('pool1')
            ),
            new Map(
                array(
                    'app_data' => new ConnectionPoolPair(
                        new ConnectionPool('master1', new Vector(array($expectedConnections->get('master1')))),
                        $expectedPools->get('pool1')
                    ),
                    'app_reporting' => new ConnectionPoolPair(
                        new ConnectionPool('reporting2', new Vector(array($expectedConnections->get('reporting2')))),
                        $expectedPools->get('pool2')
                    ),
                    'app_temp' => new ConnectionPoolPair(
                        $expectedPools->get('pool2'),
                        $expectedPools->get('pool2')
                    ),
                    'app_read_only' => new ConnectionPoolPair(
                        null,
                        new ConnectionPool('master2', new Vector(array($expectedConnections->get('master2'))))
                    ),
                    'app_write_only' => new ConnectionPoolPair(
                        new ConnectionPool('master2', new Vector(array($expectedConnections->get('master2'))))
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
        $this->assertEquals($expectedConnections->elements(), $configurationSplit->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedPools->elements(), $configurationSplit->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionPoolSelector());
        $this->assertEquals($expectedSelector, $configurationSplit->connectionPoolSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configurationSplit->replicationTrees()));
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
        $configuration = $this->reader->readString($string);
        $expectedConnections = new Map(
            array(
                'foo' => new LazyConnection('foo', 'mysql:host=foo', new EnvironmentVariable('USERNAME'), '$ESCAPED'),
                'bar' => new LazyConnection('bar', 'mysql:host=bar', '\\$ESCAPED', 'IG$NORED'),
            )
        );
        $expectedPools = new Map;
        $expectedPool = new ConnectionPool('foo', new Vector(array($expectedConnections->get('foo'))));
        $expectedSelector = new ConnectionPoolSelector(
            new ConnectionPoolPair($expectedPool, $expectedPool)
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
}
