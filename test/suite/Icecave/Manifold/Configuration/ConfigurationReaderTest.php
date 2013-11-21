<?php
namespace Icecave\Manifold\Configuration;

use Eloquent\Schemer\Loader\Exception\LoadException;
use Eloquent\Schemer\Uri\Uri;
use Icecave\Isolator\Isolator;
use Icecave\Manifold\Authentication\Credentials;
use Icecave\Manifold\Authentication\CredentialsProvider;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Connection\Container\ConnectionContainerPair;
use Icecave\Manifold\Connection\Container\ConnectionContainerSelector;
use Icecave\Manifold\Connection\Container\ConnectionPool;
use Icecave\Manifold\Connection\LazyConnection;
use Icecave\Manifold\Replication\ReplicationTree;
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
        $expectedConnections = array(
            'foo' => new LazyConnection(
                'foo',
                'mysql:host=foo',
                new CredentialsProvider,
                array(PDO::ATTR_PERSISTENT => false)
            ),
        );
        $expectedPools = array();
        $expectedContainer = $expectedConnections['foo'];
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections['foo']);
        $expectedReplicationTrees = array($expectedReplicationTree);

        $this->assertEquals($expectedConnections, $configuration->connections());
        $this->assertEquals($expectedPools, $configuration->connectionPools());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertEquals($expectedReplicationTrees, $configuration->replicationTrees());
    }

    public function testConfigurationFromStringCustomFactory()
    {
        $string = <<<'EOD'
connections:
    foo: mysql:host=foo
EOD;
        $configuration = $this->reader->readString($string, null, $this->connectionFactory);
        $expectedConnections = array(
            'foo' => new LazyConnection(
                'foo',
                'mysql:host=foo',
                $this->credentialsProvider,
                array(PDO::ATTR_PERSISTENT => false)
            ),
        );
        $expectedPools = array();
        $expectedContainer = $expectedConnections['foo'];
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections['foo']);
        $expectedReplicationTrees = array($expectedReplicationTree);

        $this->assertEquals($expectedConnections, $configuration->connections());
        $this->assertEquals($expectedPools, $configuration->connectionPools());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertEquals($expectedReplicationTrees, $configuration->replicationTrees());
    }

    public function testMinimalConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-minimal.yml');
        $expectedConnections = array(
            'foo' => new LazyConnection(
                'foo',
                'mysql:host=foo',
                new CredentialsProvider,
                array(PDO::ATTR_PERSISTENT => false)
            ),
        );
        $expectedPools = array();
        $expectedContainer = $expectedConnections['foo'];
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections['foo']);
        $expectedReplicationTrees = array($expectedReplicationTree);

        $this->assertEquals($expectedConnections, $configuration->connections());
        $this->assertEquals($expectedPools, $configuration->connectionPools());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertEquals($expectedReplicationTrees, $configuration->replicationTrees());
    }

    public function testConfigurationCustomFactory()
    {
        $configuration = $this->reader->readFile(
            $this->fixturePath . '/valid-minimal.yml',
            null,
            $this->connectionFactory
        );
        $expectedConnections = array(
            'foo' => new LazyConnection(
                'foo',
                'mysql:host=foo',
                $this->credentialsProvider,
                array(PDO::ATTR_PERSISTENT => false)
            ),
        );
        $expectedPools = array();
        $expectedContainer = $expectedConnections['foo'];
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair($expectedContainer, $expectedContainer)
        );
        $expectedReplicationTree = new ReplicationTree($expectedConnections['foo']);
        $expectedReplicationTrees = array($expectedReplicationTree);

        $this->assertEquals($expectedConnections, $configuration->connections());
        $this->assertEquals($expectedPools, $configuration->connectionPools());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertEquals($expectedReplicationTrees, $configuration->replicationTrees());
    }

    public function testFullConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-full.yml');
        $configurationSplit = $this->reader->readFile($this->fixturePath . '/valid-full-split.yml');
        $expectedConnections = array(
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
        );
        $expectedPools = array(
            'pool1' => new ConnectionPool(
                'pool1',
                array(
                    $expectedConnections['slave101'],
                    $expectedConnections['slave102'],
                )
            ),
            'pool2' => new ConnectionPool(
                'pool2',
                array(
                    $expectedConnections['slave201'],
                    $expectedConnections['slave202'],
                )
            ),
        );
        $expectedSelector = new ConnectionContainerSelector(
            new ConnectionContainerPair(
                $expectedConnections['reporting1'],
                $expectedPools['pool1']
            ),
            array(
                'app_data' => new ConnectionContainerPair(
                    $expectedConnections['master1'],
                    $expectedPools['pool1']
                ),
                'app_reporting' => new ConnectionContainerPair(
                    $expectedConnections['reporting2'],
                    $expectedPools['pool2']
                ),
                'app_temp' => new ConnectionContainerPair(
                    $expectedPools['pool2'],
                    $expectedPools['pool2']
                ),
                'app_read_only' => new ConnectionContainerPair(
                    null,
                    $expectedConnections['master2']
                ),
                'app_write_only' => new ConnectionContainerPair(
                    $expectedConnections['master2']
                ),
            )
        );
        $expectedReplicationTreeA = new ReplicationTree($expectedConnections['master1']);
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['master1'],
            $expectedConnections['master2']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['master1'],
            $expectedConnections['reporting1']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['reporting1'],
            $expectedConnections['slave101']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['reporting1'],
            $expectedConnections['slave102']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['master1'],
            $expectedConnections['reporting2']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['reporting2'],
            $expectedConnections['slave201']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['reporting2'],
            $expectedConnections['slave202']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['master1'],
            $expectedConnections['reporting3']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['reporting3'],
            $expectedConnections['slave301']
        );
        $expectedReplicationTreeA->addSlave(
            $expectedConnections['reporting3'],
            $expectedConnections['slave302']
        );
        $expectedReplicationTreeB = new ReplicationTree($expectedConnections['master3']);
        $expectedReplicationTrees = array($expectedReplicationTreeA, $expectedReplicationTreeB);

        $this->assertEquals($expectedConnections, $configuration->connections());
        $this->assertEquals($expectedConnections, $configurationSplit->connections());
        $this->assertEquals($expectedPools, $configuration->connectionPools());
        $this->assertEquals($expectedPools, $configurationSplit->connectionPools());
        $this->assertEquals($expectedSelector, $configuration->connectionContainerSelector());
        $this->assertEquals($expectedSelector, $configurationSplit->connectionContainerSelector());
        $this->assertEquals($expectedReplicationTrees, $configuration->replicationTrees());
        $this->assertEquals($expectedReplicationTrees, $configurationSplit->replicationTrees());
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
