<?php
namespace Icecave\Manifold\Authentication\Caching;

use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

class CachingCredentialsReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->innerReader = Phake::mock('Icecave\Manifold\Authentication\CredentialsReader');
        $this->generator = Phake::partialMock(__NAMESPACE__ . '\CredentialsCacheFileGenerator');
        $this->isolator = Phake::mock(Isolator::className());
        $this->reader = new CachingCredentialsReader($this->innerReader, $this->generator, $this->isolator);

        $this->provider = Phake::mock('Icecave\Manifold\Authentication\StaticCredentialsProviderInterface');

        Phake::when($this->generator)->generateForProvider(Phake::anyParameters())->thenReturn(null);
    }

    public function testConstructor()
    {
        $this->assertSame($this->innerReader, $this->reader->reader());
        $this->assertSame($this->generator, $this->reader->generator());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new CachingCredentialsReader;

        $this->assertInstanceOf('Icecave\Manifold\Authentication\CredentialsReader', $this->reader->reader());
        $this->assertInstanceOf(__NAMESPACE__ . '\CredentialsCacheFileGenerator', $this->reader->generator());
    }

    public function testReadFileNotCached()
    {
        Phake::when($this->isolator)->is_file('/path/to/credentials.yml.cache.php')->thenReturn(false);
        Phake::when($this->innerReader)->readFile('/path/to/credentials.yml', 'mimeType')
            ->thenReturn($this->provider);

        $this->assertSame($this->provider, $this->reader->readFile('/path/to/credentials.yml', 'mimeType'));
        Phake::inOrder(
            Phake::verify($this->innerReader)->readFile('/path/to/credentials.yml', 'mimeType'),
            Phake::verify($this->generator)->generateForProvider($this->provider, '/path/to/credentials.yml.cache.php')
        );
        Phake::verify($this->isolator, Phake::never())->require(Phake::anyParameters());
    }

    public function testReadFileCached()
    {
        $provider = $this->provider;
        $factoryCalled = false;
        Phake::when($this->isolator)->is_file('/path/to/credentials.yml.cache.php')->thenReturn(true);
        Phake::when($this->isolator)->require('/path/to/credentials.yml.cache.php')->thenReturn(
            function () use ($provider, &$factoryCalled) {
                $factoryCalled = true;

                return $provider;
            }
        );

        $this->assertSame($this->provider, $this->reader->readFile('/path/to/credentials.yml', 'mimeType'));
        $this->assertTrue($factoryCalled);
        Phake::verify($this->innerReader, Phake::never())->readFile(Phake::anyParameters());
        Phake::verify($this->generator, Phake::never())->generateForProvider(Phake::anyParameters());
    }

    public function testReadString()
    {
        Phake::when($this->innerReader)->readString('foo', 'mimeType')->thenReturn($this->provider);

        $this->assertSame($this->provider, $this->reader->readString('foo', 'mimeType'));
    }
}
