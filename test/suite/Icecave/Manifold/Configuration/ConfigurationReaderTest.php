<?php
namespace Icecave\Manifold\Configuration;

use Eloquent\Schemer\Loader\Exception\LoadException;
use Eloquent\Schemer\Uri\Uri;
use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Isolator\Isolator;
use Icecave\Manifold\Authentication\Credentials;
use Icecave\Manifold\Authentication\CredentialsProvider;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Connection\Container\ConnectionContainerPair;
use Icecave\Manifold\Connection\Container\ConnectionContainerSelector;
use Icecave\Manifold\Connection\Container\ConnectionPool;
use Icecave\Manifold\Connection\LazyConnection;
use Icecave\Manifold\Replication\ReplicationTree;
use Icecave\Parity\Parity;
use PDO;
use PHPUnit_Framework_TestCase;
use Phake;

class ConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock(Isolator::className());
        $this->innerReader = Phake::mock('Eloquent\Schemer\Reader\ReaderInterface');
        $this->defaultConnectionFactory = new ConnectionFactory;

        $this->reader = new ConfigurationReader(null, $this->defaultConnectionFactory);

        $this->fixturePath = __DIR__ . '/../../../../fixture/config';

        $this->credentialsProvider = new CredentialsProvider(new Credentials('username', 'password'));
        $this->connectionFactory = new ConnectionFactory($this->credentialsProvider);
    }

    public function testConstructor()
    {
        $this->reader = new ConfigurationReader($this->innerReader, $this->defaultConnectionFactory);

        $this->assertSame($this->innerReader, $this->reader->reader());
        $this->assertSame($this->defaultConnectionFactory, $this->reader->defaultConnectionFactory());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new ConfigurationReader;

        $this->assertInstanceOf('Eloquent\Schemer\Reader\ValidatingReader', $this->reader->reader());
        $this->assertInstanceOf(
            'Icecave\Manifold\Connection\ConnectionFactory',
            $this->reader->defaultConnectionFactory()
        );
    }

    public function testConfigurationFromString()
    {
        $string = <<<'EOD'
connections:
    foo: mysql:host=foo
EOD;
        $configuration = $this->reader->readString($string);
        $expectedConnections = new Map(
            array(
                'foo' => new LazyConnection(
                    'foo',
                    'mysql:host=foo',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
            )
        );
        $expectedPools = new Map;
        $expectedContainer = $expectedConnections->get('foo');
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections->get('foo'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTree));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function testConfigurationFromStringCustomFactory()
    {
        $string = <<<'EOD'
connections:
    foo: mysql:host=foo
EOD;
        $configuration = $this->reader->readString($string, null, $this->connectionFactory);
        $expectedConnections = new Map(
            array(
                'foo' => new LazyConnection(
                    'foo',
                    'mysql:host=foo',
                    $this->credentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
            )
        );
        $expectedPools = new Map;
        $expectedContainer = $expectedConnections->get('foo');
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections->get('foo'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTree));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function testMinimalConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-minimal.yml');
        $expectedConnections = new Map(
            array(
                'foo' => new LazyConnection(
                    'foo',
                    'mysql:host=foo',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
            )
        );
        $expectedPools = new Map;
        $expectedContainer = $expectedConnections->get('foo');
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections->get('foo'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTree));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function testConfigurationCustomFactory()
    {
        $configuration = $this->reader->readFile(
            $this->fixturePath . '/valid-minimal.yml',
            null,
            $this->connectionFactory
        );
        $expectedConnections = new Map(
            array(
                'foo' => new LazyConnection(
                    'foo',
                    'mysql:host=foo',
                    $this->credentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
            )
        );
        $expectedPools = new Map;
        $expectedContainer = $expectedConnections->get('foo');
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections->get('foo'));
        $expectedReplicationTrees = new Vector(array($expectedReplicationTree));

        $this->assertEquals($expectedConnections->elements(), $configuration->connections()->elements());
        $this->assertEquals($expectedPools->elements(), $configuration->connectionPools()->elements());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
    }

    public function testFullConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-full.yml');
        $configurationSplit = $this->reader->readFile($this->fixturePath . '/valid-full-split.yml');
        $expectedConnections = new Map(
            array(
                'master1' => new LazyConnection(
                    'master1',
                    'mysql:host=master1',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'master2' => new LazyConnection(
                    'master2',
                    'mysql:host=master2',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'master3' => new LazyConnection(
                    'master3',
                    'mysql:host=master3',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'reporting1' => new LazyConnection(
                    'reporting1',
                    'mysql:host=reporting1',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'slave101' => new LazyConnection(
                    'slave101',
                    'mysql:host=slave101',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'slave102' => new LazyConnection(
                    'slave102',
                    'mysql:host=slave102',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'reporting2' => new LazyConnection(
                    'reporting2',
                    'mysql:host=reporting2',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'slave201' => new LazyConnection(
                    'slave201',
                    'mysql:host=slave201',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'slave202' => new LazyConnection(
                    'slave202',
                    'mysql:host=slave202',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'reporting3' => new LazyConnection(
                    'reporting3',
                    'mysql:host=reporting3',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'slave301' => new LazyConnection(
                    'slave301',
                    'mysql:host=slave301',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
                'slave302' => new LazyConnection(
                    'slave302',
                    'mysql:host=slave302',
                    new CredentialsProvider,
                    array(PDO::ATTR_PERSISTENT => false)
                ),
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
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair(
                $expectedConnections->get('reporting1'),
                $expectedPools->get('pool1')
            ),
            new Map(
                array(
                    'app_data' => new ConnectionContainerPair(
                        $expectedConnections->get('master1'),
                        $expectedPools->get('pool1')
                    ),
                    'app_reporting' => new ConnectionContainerPair(
                        $expectedConnections->get('reporting2'),
                        $expectedPools->get('pool2')
                    ),
                    'app_temp' => new ConnectionContainerPair(
                        $expectedPools->get('pool2'),
                        $expectedPools->get('pool2')
                    ),
                    'app_read_only' => new ConnectionContainerPair(
                        null,
                        $expectedConnections->get('master2')
                    ),
                    'app_write_only' => new ConnectionContainerPair(
                        $expectedConnections->get('master2')
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
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertEquals($expectedSelector, $configurationSplit->connectionContainerSelector());
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configuration->replicationTrees()));
        $this->assertSame(0, Parity::compare($expectedReplicationTrees, $configurationSplit->replicationTrees()));
    }

    public function invalidConfigurationData()
    {
        //                                  fixtureName             expected
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

    public function testConfigurationFileReadFailure()
    {
        $this->reader = new ConfigurationReader($this->innerReader);
        Phake::when($this->innerReader)->readPath(Phake::anyParameters())
            ->thenThrow(new LoadException(new Uri('file:///foo')));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ConfigurationReadException');
        $this->reader->readFile('foo');
    }
}
