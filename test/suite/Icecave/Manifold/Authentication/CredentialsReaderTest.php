<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Collections\Map;
use Icecave\Parity\Parity;
use PHPUnit_Framework_TestCase;
use Phake;

class CredentialsReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->reader = new CredentialsReader;

        $this->innerReader = Phake::mock('Eloquent\Schemer\Reader\ReaderInterface');

        $this->fixturePath = __DIR__ . '/../../../../fixture/credentials';
    }

    public function testConstructor()
    {
        $this->reader = new CredentialsReader($this->innerReader);

        $this->assertSame($this->innerReader, $this->reader->reader());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new CredentialsReader;

        $this->assertInstanceOf('Eloquent\Schemer\Reader\ValidatingReader', $this->reader->reader());
    }

    public function testCredentialsFromString()
    {
        $string = <<<'EOD'
default:
    username: defaultUsername
EOD;
        $actual = $this->reader->readString($string);
        $expected = new CredentialsProvider(
            new Credentials('defaultUsername')
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame(0, Parity::compare($expected, $actual));
    }

    public function testCredentialsMinimal()
    {
        $actual = $this->reader->readFile($this->fixturePath . '/valid-minimal.yml');
        $expected = new CredentialsProvider(
            new Credentials('defaultUsername')
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame(0, Parity::compare($expected, $actual));
    }

    public function testCredentialsFull()
    {
        $actual = $this->reader->readFile($this->fixturePath . '/valid-full.yml');
        $expected = new CredentialsProvider(
            new Credentials('defaultUsername', 'defaultPassword'),
            new Map(
                array(
                    'foo' => new Credentials('fooUsername', 'fooPassword'),
                    'bar' => new Credentials('barUsername', 'barPassword'),
                )
            )
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame(0, Parity::compare($expected, $actual));
    }

    public function testCredentialsNoDefault()
    {
        $string = <<<'EOD'
connections:
    foo:
        password: fooPassword
EOD;
        $actual = $this->reader->readString($string);
        $expected = new CredentialsProvider(
            new Credentials,
            new Map(
                array(
                    'foo' => new Credentials(null, 'fooPassword'),
                )
            )
        );

        $this->assertEquals($expected, $actual);
        $this->assertSame(0, Parity::compare($expected, $actual));
    }
}
