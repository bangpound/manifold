<?php
namespace Icecave\Manifold;

use Icecave\Manifold\Authentication\Caching\CachingCredentialsReader;
use Icecave\Manifold\Configuration\Caching\CachingConfigurationReader;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Mysql\MysqlDriver;
use PDO;
use PHPUnit_Framework_TestCase;
use Phake;

class ManifoldFactoryTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->driver = Phake::mock('Icecave\Manifold\Driver\DriverInterface');
        $this->configurationReader = Phake::mock('Icecave\Manifold\Configuration\ConfigurationReaderInterface');
        $this->credentialsReader = Phake::mock('Icecave\Manifold\Authentication\CredentialsReaderInterface');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->factory = new ManifoldFactory(
            $this->driver,
            $this->configurationReader,
            $this->credentialsReader,
            $this->logger
        );

        $this->configuration = Phake::mock('Icecave\Manifold\Configuration\ConfigurationInterface');
        $this->credentialsProvider = Phake::mock('Icecave\Manifold\Authentication\CredentialsProviderInterface');
        $this->connectionFactory = new ConnectionFactory($this->credentialsProvider, null, null, $this->logger);
        $this->attributes = array(PDO::ATTR_PERSISTENT => false, PDO::ATTR_AUTOCOMMIT => false);
        $this->facade = Phake::mock('Icecave\Manifold\Connection\Facade\ConnectionFacadeInterface');
    }

    public function testConstructor()
    {
        $this->assertSame($this->driver, $this->factory->driver());
        $this->assertSame($this->configurationReader, $this->factory->configurationReader());
        $this->assertSame($this->credentialsReader, $this->factory->credentialsReader());
        $this->assertSame($this->logger, $this->factory->logger());
    }

    public function testConstructorDefaults()
    {
        $this->factory = new ManifoldFactory();

        $this->assertEquals(new MysqlDriver(), $this->factory->driver());
        $this->assertEquals(new CachingConfigurationReader(), $this->factory->configurationReader());
        $this->assertEquals(new CachingCredentialsReader(), $this->factory->credentialsReader());
        $this->assertNull($this->factory->logger());
    }

    public function testSetLogger()
    {
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->factory->setLogger($this->logger);

        $this->assertSame($this->logger, $this->factory->logger());
    }

    public function testCreateWithPathStrings()
    {
        Phake::when($this->credentialsReader)->readFile('/path/to/manifold-credentials.yml')
            ->thenReturn($this->credentialsProvider);
        Phake::when($this->configurationReader)->readFile('/path/to/manifold.yml', null, $this->connectionFactory)
            ->thenReturn($this->configuration);
        Phake::when($this->driver)->createConnectionByName($this->configuration, 'connectionName', $this->attributes)
            ->thenReturn($this->facade);
        $actual = $this->factory->create(
            '/path/to/manifold.yml',
            '/path/to/manifold-credentials.yml',
            'connectionName',
            $this->attributes
        );

        $this->assertSame($this->facade, $actual);
        Phake::verify($this->facade)->setLogger($this->logger);
    }

    public function testCreateWithPathStringsDefaults()
    {
        Phake::when($this->configurationReader)
            ->readFile('/path/to/manifold.yml', null, new ConnectionFactory(null, null, null, $this->logger))
            ->thenReturn($this->configuration);
        Phake::when($this->driver)->createFirstConnection($this->configuration, null)->thenReturn($this->facade);
        $actual = $this->factory->create('/path/to/manifold.yml');

        $this->assertSame($this->facade, $actual);
        Phake::verify($this->facade)->setLogger($this->logger);
    }

    public function testCreateWithObjects()
    {
        Phake::when($this->driver)->createConnectionByName($this->configuration, 'connectionName', $this->attributes)
            ->thenReturn($this->facade);
        $actual = $this->factory->create(
            $this->configuration,
            $this->credentialsProvider,
            'connectionName',
            $this->attributes
        );

        $this->assertSame($this->facade, $actual);
        Phake::verify($this->facade)->setLogger($this->logger);
    }

    public function testCreateWithObjectsDefaults()
    {
        Phake::when($this->driver)->createFirstConnection($this->configuration, null)->thenReturn($this->facade);
        $actual = $this->factory->create(
            $this->configuration
        );

        $this->assertSame($this->facade, $actual);
        Phake::verify($this->facade)->setLogger($this->logger);
    }
}
