<?php
namespace Icecave\Manifold\Connection;

use Icecave\Manifold\Authentication\CredentialsProvider;
use PDO;
use PHPUnit_Framework_TestCase;
use Phake;

class ConnectionFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->credentialsProvider = new CredentialsProvider;
        $this->attributes = array('foo' => 'bar');
        $this->pdoConnectionFactory = Phake::mock('Icecave\Manifold\Connection\PdoConnectionFactoryInterface');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->factory = new ConnectionFactory(
            $this->credentialsProvider,
            $this->attributes,
            $this->pdoConnectionFactory,
            $this->logger
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->credentialsProvider, $this->factory->credentialsProvider());
        $this->assertSame($this->attributes, $this->factory->attributes());
        $this->assertSame($this->pdoConnectionFactory, $this->factory->pdoConnectionFactory());
        $this->assertSame($this->logger, $this->factory->logger());
    }

    public function testConstructorDefaults()
    {
        $this->factory = new ConnectionFactory;

        $this->assertEquals($this->credentialsProvider, $this->factory->credentialsProvider());
        $this->assertSame(array(PDO::ATTR_PERSISTENT => false), $this->factory->attributes());
        $this->assertEquals(new PdoConnectionFactory, $this->factory->pdoConnectionFactory());
        $this->assertNull($this->factory->logger());
    }

    public function testSetLogger()
    {
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->factory->setLogger($this->logger);

        $this->assertSame($this->logger, $this->factory->logger());
    }

    public function testCreate()
    {
        $expected = new LazyConnection(
            'name',
            'dsn',
            $this->credentialsProvider,
            array('foo' => 'bar'),
            $this->pdoConnectionFactory,
            $this->logger
        );

        $this->assertEquals($expected, $this->factory->create('name', 'dsn', 'username', 'password'));
    }
}
