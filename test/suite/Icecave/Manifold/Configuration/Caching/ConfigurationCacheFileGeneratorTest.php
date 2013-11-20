<?php
namespace Icecave\Manifold\Configuration\Caching;

use ErrorException;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

class ConfigurationCacheFileGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->reader = Phake::mock('Icecave\Manifold\Configuration\ConfigurationReader');
        $this->innerGenerator = Phake::mock(__NAMESPACE__ . '\ConfigurationCacheGenerator');
        $this->isolator = Phake::mock(Isolator::className());
        $this->generator = Phake::partialMock(
            __NAMESPACE__ . '\ConfigurationCacheFileGenerator',
            $this->reader,
            $this->innerGenerator,
            $this->isolator
        );

        $this->configuration = Phake::mock('Icecave\Manifold\Configuration\ConfigurationInterface');
        $this->connectionFactory = Phake::mock('Icecave\Manifold\Connection\ConnectionFactoryInterface');
        $this->fixturePath = __DIR__ . '/../../../../../fixture/config';
    }

    public function testConstructor()
    {
        $this->assertSame($this->reader, $this->generator->reader());
        $this->assertSame($this->innerGenerator, $this->generator->generator());
    }

    public function testConstructorDefaults()
    {
        $this->generator = new ConfigurationCacheFileGenerator;

        $this->assertInstanceOf('Icecave\Manifold\Configuration\ConfigurationReader', $this->generator->reader());
        $this->assertInstanceOf(__NAMESPACE__ . '\ConfigurationCacheGenerator', $this->generator->generator());
    }

    public function testGenerate()
    {
        Phake::when($this->reader)->readFile('/path/to/configuration.yml', 'mimeType', $this->connectionFactory)
            ->thenReturn($this->configuration);
        Phake::when($this->innerGenerator)->generate($this->configuration)->thenReturn('function () {}');
        $this->generator->generate(
            '/path/to/configuration.yml',
            '/path/to/cache.php',
            'mimeType',
            $this->connectionFactory
        );

        Phake::verify($this->isolator)->file_put_contents('/path/to/cache.php', "<?php\n\nreturn function () {};\n");
    }

    public function testGenerateDefaults()
    {
        Phake::when($this->reader)->readFile('/path/to/configuration.yml', null, null)
            ->thenReturn($this->configuration);
        Phake::when($this->innerGenerator)->generate($this->configuration)->thenReturn('function () {}');
        $this->generator->generate('/path/to/configuration.yml');

        Phake::verify($this->isolator)
            ->file_put_contents('/path/to/configuration.yml.cache.php', "<?php\n\nreturn function () {};\n");
    }

    public function testGenerateForConfiguration()
    {
        Phake::when($this->innerGenerator)->generate($this->configuration)->thenReturn('function () {}');
        $this->generator->generateForConfiguration($this->configuration, '/path/to/cache.php');

        Phake::verify($this->isolator)->file_put_contents('/path/to/cache.php', "<?php\n\nreturn function () {};\n");
    }

    public function testGenerateForConfigurationFailure()
    {
        Phake::when($this->innerGenerator)->generate($this->configuration)->thenReturn('function () {}');
        Phake::when($this->isolator)->file_put_contents(Phake::anyParameters())->thenThrow(new ErrorException);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\ConfigurationCacheWriteException');
        $this->generator->generateForConfiguration($this->configuration, '/path/to/cache.php');
    }

    public function testDefaultCachePath()
    {
        $this->assertSame(
            '/path/to/configuration.yml.cache.php',
            $this->generator->defaultCachePath('/path/to/configuration.yml')
        );
    }
}
