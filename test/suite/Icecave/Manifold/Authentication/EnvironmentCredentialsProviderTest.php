<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Collections\Map;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Icecave\Manifold\Authentication\EnvironmentCredentialsProvider
 * @covers \Icecave\Manifold\Authentication\AbstractCredentialsProvider
 */
class EnvironmentCredentialsProviderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->defaultCredentials = new Credentials('USERNAME_DEFAULT', 'PASSWORD_DEFAULT');
        $this->fooCredentials = new Credentials('USERNAME_FOO', 'PASSWORD_FOO');
        $this->barCredentials = new Credentials('USERNAME_BAR');
        $this->quxCredentials = new Credentials('USERNAME_QUX');
        $this->connectionCredentials = new Map(
            array(
                'foo' => $this->fooCredentials,
                'bar' => $this->barCredentials,
                'qux' => $this->quxCredentials,
            )
        );
        $this->isolator = Phake::mock(Isolator::className());
        $this->provider = new EnvironmentCredentialsProvider(
            $this->defaultCredentials,
            $this->connectionCredentials,
            $this->isolator
        );

        $this->resolvedDefaultCredentials = new Credentials('defaultUsername', 'defaultPassword');
        $this->resolvedFooCredentials = new Credentials('fooUsername', 'fooPassword');
        $this->resolvedBarCredentials = new Credentials('barUsername');

        Phake::when($this->isolator)->getenv('USERNAME_DEFAULT')->thenReturn('defaultUsername');
        Phake::when($this->isolator)->getenv('PASSWORD_DEFAULT')->thenReturn('defaultPassword');
        Phake::when($this->isolator)->getenv('USERNAME_FOO')->thenReturn('fooUsername');
        Phake::when($this->isolator)->getenv('PASSWORD_FOO')->thenReturn('fooPassword');
        Phake::when($this->isolator)->getenv('USERNAME_BAR')->thenReturn('barUsername');
        Phake::when($this->isolator)->getenv('USERNAME_QUX')->thenReturn(false);

        $this->fooConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->fooConnection)->name()->thenReturn('foo');
        $this->barConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->barConnection)->name()->thenReturn('bar');
        $this->bazConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->bazConnection)->name()->thenReturn('baz');
        $this->quxConnection = Phake::mock('Icecave\Manifold\Connection\ConnectionInterface');
        Phake::when($this->quxConnection)->name()->thenReturn('qux');
    }

    public function testConstructor()
    {
        $this->assertSame($this->defaultCredentials, $this->provider->defaultCredentials());
        $this->assertSame($this->connectionCredentials, $this->provider->connectionCredentials());
    }

    public function testConstructorDefaults()
    {
        $this->provider = new EnvironmentCredentialsProvider;

        $this->assertEquals(new Credentials, $this->provider->defaultCredentials());
        $this->assertEquals(new Map, $this->provider->connectionCredentials());
    }

    public function testForConnection()
    {
        $this->assertEquals($this->resolvedFooCredentials, $this->provider->forConnection($this->fooConnection));
        $this->assertEquals($this->resolvedBarCredentials, $this->provider->forConnection($this->barConnection));
        $this->assertEquals($this->resolvedDefaultCredentials, $this->provider->forConnection($this->bazConnection));
    }

    public function testForConnectionFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UndefinedCredentialsException');
        $this->provider->forConnection($this->quxConnection);
    }
}
