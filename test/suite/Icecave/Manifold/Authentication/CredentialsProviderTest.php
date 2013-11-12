<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Collections\Map;
use Phake;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Icecave\Manifold\Authentication\CredentialsProvider
 * @covers \Icecave\Manifold\Authentication\AbstractCredentialsProvider
 */
class CredentialsProviderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->defaultCredentials = new Credentials('defaultUsername', 'defaultPassword');
        $this->fooCredentials = new Credentials('fooUsername', 'fooPassword');
        $this->barCredentials = new Credentials('barUsername', 'barPassword');
        $this->connectionCredentials = new Map(array('foo' => $this->fooCredentials, 'bar' => $this->barCredentials));
        $this->provider = new CredentialsProvider($this->defaultCredentials, $this->connectionCredentials);

        $this->fooConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->fooConnection)->name()->thenReturn('foo');
        $this->barConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->barConnection)->name()->thenReturn('bar');
        $this->bazConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->bazConnection)->name()->thenReturn('baz');
    }

    public function testConstructor()
    {
        $this->assertSame($this->defaultCredentials, $this->provider->defaultCredentials());
        $this->assertSame($this->connectionCredentials, $this->provider->connectionCredentials());
    }

    public function testConstructorDefaults()
    {
        $this->provider = new CredentialsProvider;

        $this->assertEquals(new Credentials, $this->provider->defaultCredentials());
        $this->assertEquals(new Map, $this->provider->connectionCredentials());
    }

    public function testForConnection()
    {
        $this->assertSame($this->fooCredentials, $this->provider->forConnection($this->fooConnection));
        $this->assertSame($this->barCredentials, $this->provider->forConnection($this->barConnection));
        $this->assertSame($this->defaultCredentials, $this->provider->forConnection($this->bazConnection));
    }
}
