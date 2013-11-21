<?php
namespace Icecave\Manifold\Authentication\Caching;

use ErrorException;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

class CredentialsCacheFileGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->reader = Phake::mock('Icecave\Manifold\Authentication\CredentialsReaderInterface');
        $this->innerGenerator = Phake::mock(__NAMESPACE__ . '\CredentialsCacheGenerator');
        $this->isolator = Phake::mock(Isolator::className());
        $this->generator = Phake::partialMock(
            __NAMESPACE__ . '\CredentialsCacheFileGenerator',
            $this->reader,
            $this->innerGenerator,
            $this->isolator
        );

        $this->provider = Phake::mock('Icecave\Manifold\Authentication\StaticCredentialsProviderInterface');
        $this->fixturePath = __DIR__ . '/../../../../../fixture/config';
    }

    public function testConstructor()
    {
        $this->assertSame($this->reader, $this->generator->reader());
        $this->assertSame($this->innerGenerator, $this->generator->generator());
    }

    public function testConstructorDefaults()
    {
        $this->generator = new CredentialsCacheFileGenerator;

        $this->assertInstanceOf('Icecave\Manifold\Authentication\CredentialsReader', $this->generator->reader());
        $this->assertInstanceOf(__NAMESPACE__ . '\CredentialsCacheGenerator', $this->generator->generator());
    }

    public function testGenerate()
    {
        Phake::when($this->reader)->readFile('/path/to/credentials.yml', 'mimeType')->thenReturn($this->provider);
        Phake::when($this->innerGenerator)->generate($this->provider)->thenReturn('function () {}');
        $this->generator->generate('/path/to/credentials.yml', '/path/to/cache.php', 'mimeType');

        Phake::verify($this->isolator)->file_put_contents('/path/to/cache.php', "<?php\n\nreturn function () {};\n");
    }

    public function testGenerateDefaults()
    {
        Phake::when($this->reader)->readFile('/path/to/credentials.yml', null)
            ->thenReturn($this->provider);
        Phake::when($this->innerGenerator)->generate($this->provider)->thenReturn('function () {}');
        $this->generator->generate('/path/to/credentials.yml');

        Phake::verify($this->isolator)
            ->file_put_contents('/path/to/credentials.yml.cache.php', "<?php\n\nreturn function () {};\n");
    }

    public function testGenerateForProvider()
    {
        Phake::when($this->innerGenerator)->generate($this->provider)->thenReturn('function () {}');
        $this->generator->generateForProvider($this->provider, '/path/to/cache.php');

        Phake::verify($this->isolator)->file_put_contents('/path/to/cache.php', "<?php\n\nreturn function () {};\n");
    }

    public function testGenerateForProviderFailure()
    {
        Phake::when($this->innerGenerator)->generate($this->provider)->thenReturn('function () {}');
        Phake::when($this->isolator)->file_put_contents(Phake::anyParameters())->thenThrow(new ErrorException);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\CredentialsCacheWriteException');
        $this->generator->generateForProvider($this->provider, '/path/to/cache.php');
    }

    public function testDefaultCachePath()
    {
        $this->assertSame(
            '/path/to/credentials.yml.cache.php',
            $this->generator->defaultCachePath('/path/to/credentials.yml')
        );
    }
}
