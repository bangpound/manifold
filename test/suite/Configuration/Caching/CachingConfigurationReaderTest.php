<?php
namespace Icecave\Manifold\Configuration\Caching;

use ErrorException;
use Icecave\Isolator\Isolator;
use Icecave\Manifold\Connection\ConnectionFactoryInterface;
use PHPUnit_Framework_TestCase;
use Phake;

class CachingConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->innerReader = Phake::mock('Icecave\Manifold\Configuration\ConfigurationReader');
        $this->generator = Phake::partialMock(__NAMESPACE__ . '\ConfigurationCacheFileGenerator');
        $this->isolator = Phake::mock(Isolator::className());
        $this->reader = new CachingConfigurationReader($this->innerReader, $this->generator, $this->isolator);

        $this->configuration = Phake::mock('Icecave\Manifold\Configuration\ConfigurationInterface');
        $this->connectionFactory = Phake::mock('Icecave\Manifold\Connection\ConnectionFactoryInterface');

        Phake::when($this->generator)->generateForConfiguration(Phake::anyParameters())->thenReturn(null);
    }

    public function testConstructor()
    {
        $this->assertSame($this->innerReader, $this->reader->reader());
        $this->assertSame($this->generator, $this->reader->generator());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new CachingConfigurationReader();

        $this->assertInstanceOf('Icecave\Manifold\Configuration\ConfigurationReader', $this->reader->reader());
        $this->assertInstanceOf(__NAMESPACE__ . '\ConfigurationCacheFileGenerator', $this->reader->generator());
    }

    public function testReadFileNotCached()
    {
        Phake::when($this->isolator)->include('/path/to/configuration.yml.cache.php')->thenThrow(new ErrorException());
        Phake::when($this->innerReader)->readFile('/path/to/configuration.yml', 'mimeType', $this->connectionFactory)
            ->thenReturn($this->configuration);

        $this->assertSame(
            $this->configuration,
            $this->reader->readFile('/path/to/configuration.yml', 'mimeType', $this->connectionFactory)
        );
        Phake::inOrder(
            Phake::verify($this->innerReader)
                ->readFile('/path/to/configuration.yml', 'mimeType', $this->connectionFactory),
            Phake::verify($this->generator)
                ->generateForConfiguration($this->configuration, '/path/to/configuration.yml.cache.php')
        );
    }

    public function testReadFileCached()
    {
        $configuration = $this->configuration;
        $factoryCalled = false;
        Phake::when($this->isolator)->include('/path/to/configuration.yml.cache.php')->thenReturn(
            function (ConnectionFactoryInterface $connectionFactory) use ($configuration, &$factoryCalled) {
                $factoryCalled = true;

                return $configuration;
            }
        );

        $this->assertSame(
            $this->configuration,
            $this->reader->readFile('/path/to/configuration.yml', 'mimeType', $this->connectionFactory)
        );
        $this->assertTrue($factoryCalled);
        Phake::verify($this->innerReader, Phake::never())->readFile(Phake::anyParameters());
        Phake::verify($this->generator, Phake::never())->generateForConfiguration(Phake::anyParameters());
    }

    public function testReadString()
    {
        Phake::when($this->innerReader)->readString('foo', 'mimeType', $this->connectionFactory)
            ->thenReturn($this->configuration);

        $this->assertSame($this->configuration, $this->reader->readString('foo', 'mimeType', $this->connectionFactory));
    }
}
