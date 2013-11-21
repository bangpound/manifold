<?php
namespace Icecave\Manifold\Authentication\Caching;

use Icecave\Manifold\Authentication\CredentialsReader;
use PHPUnit_Framework_TestCase;

class CredentialsCacheGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->generator = new CredentialsCacheGenerator;
        $this->reader = new CredentialsReader;
        $this->fixturePath = __DIR__ . '/../../../../../fixture/credentials';
    }

    public function testMinimalCredentials()
    {
        $provider = $this->reader->readFile($this->fixturePath . '/valid-minimal.yml');
        $expected = <<<'EOD'
function () {
    $connectionCredentials = new Icecave\Collections\Map;

    return new Icecave\Manifold\Authentication\CredentialsProvider(
        new Icecave\Manifold\Authentication\Credentials(
            'defaultUsername',
            null
        ),
        $connectionCredentials
    );
}
EOD;
        $actual = $this->generator->generate($provider);

        $this->assertSame($expected, $actual);

        eval('$actualProvider = call_user_func(' . $actual . ');');

        $this->assertInstanceOf('Icecave\Manifold\Authentication\CredentialsProvider', $actualProvider);
    }

    public function testFullCredentials()
    {
        $provider = $this->reader->readFile($this->fixturePath . '/valid-full.yml');
        $expected = <<<'EOD'
function () {
    $connectionCredentials = new Icecave\Collections\Map;
    $connectionCredentials->set(
        'bar',
        new Icecave\Manifold\Authentication\Credentials(
            'barUsername',
            'barPassword'
        )
    );
    $connectionCredentials->set(
        'foo',
        new Icecave\Manifold\Authentication\Credentials(
            'fooUsername',
            'fooPassword'
        )
    );

    return new Icecave\Manifold\Authentication\CredentialsProvider(
        new Icecave\Manifold\Authentication\Credentials(
            'defaultUsername',
            'defaultPassword'
        ),
        $connectionCredentials
    );
}
EOD;
        $actual = $this->generator->generate($provider);

        $this->assertSame($expected, $actual);

        eval('$actualProvider = call_user_func(' . $actual . ');');

        $this->assertInstanceOf('Icecave\Manifold\Authentication\CredentialsProvider', $actualProvider);
    }
}
