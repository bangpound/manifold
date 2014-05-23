<?php
namespace Icecave\Manifold\Configuration\Caching;

use Icecave\Manifold\Configuration\ConfigurationReader;
use PHPUnit_Framework_TestCase;

class ConfigurationCacheGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->generator = new ConfigurationCacheGenerator;
        $this->reader = new ConfigurationReader;
        $this->fixturePath = __DIR__ . '/../../../fixture/config';
    }

    public function testMinimalConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-minimal.yml');
        $expected = <<<'EOD'
function (
    Icecave\Manifold\Connection\ConnectionFactoryInterface $factory = null
) {
    if (null === $factory) {
        $factory = new Icecave\Manifold\Connection\ConnectionFactory;
    }

    $connections = array(
        'foo' => $factory->create(
            'foo',
            'mysql:host=foo'
        ),
    );

    $connectionPools = array();

    $selector = new Icecave\Manifold\Connection\Container\ConnectionContainerSelector(
        new Icecave\Manifold\Connection\Container\ConnectionContainerPair(
            $connections['foo'],
            $connections['foo']
        ),
        array()
    );

    $replicationTrees = array();
    $replicationTree = new Icecave\Manifold\Replication\ReplicationTree(
        $connections['foo']
    );
    $replicationTrees[] = $replicationTree;

    return new Icecave\Manifold\Configuration\Configuration(
        $connections,
        $connectionPools,
        $selector,
        $replicationTrees
    );
}
EOD;
        $actual = $this->generator->generate($configuration);

        $this->assertSame($expected, $actual);

        eval('$actualConfiguration = call_user_func(' . $actual . ');');

        $this->assertInstanceOf('Icecave\Manifold\Configuration\Configuration', $actualConfiguration);
        $actualConnections = $actualConfiguration->connections();
        $this->assertInstanceOf('Icecave\Manifold\Connection\LazyConnection', $actualConnections['foo']);
    }

    public function testFullConfiguration()
    {
        $configuration = $this->reader->readFile($this->fixturePath . '/valid-full.yml');
        $expected = <<<'EOD'
function (
    Icecave\Manifold\Connection\ConnectionFactoryInterface $factory = null
) {
    if (null === $factory) {
        $factory = new Icecave\Manifold\Connection\ConnectionFactory;
    }

    $connections = array(
        'master1' => $factory->create(
            'master1',
            'mysql:host=master1'
        ),
        'master2' => $factory->create(
            'master2',
            'mysql:host=master2'
        ),
        'master3' => $factory->create(
            'master3',
            'mysql:host=master3'
        ),
        'reporting1' => $factory->create(
            'reporting1',
            'mysql:host=reporting1'
        ),
        'slave101' => $factory->create(
            'slave101',
            'mysql:host=slave101'
        ),
        'slave102' => $factory->create(
            'slave102',
            'mysql:host=slave102'
        ),
        'reporting2' => $factory->create(
            'reporting2',
            'mysql:host=reporting2'
        ),
        'slave201' => $factory->create(
            'slave201',
            'mysql:host=slave201'
        ),
        'slave202' => $factory->create(
            'slave202',
            'mysql:host=slave202'
        ),
        'reporting3' => $factory->create(
            'reporting3',
            'mysql:host=reporting3'
        ),
        'slave301' => $factory->create(
            'slave301',
            'mysql:host=slave301'
        ),
        'slave302' => $factory->create(
            'slave302',
            'mysql:host=slave302'
        ),
    );

    $connectionPools = array(
        'pool1' => new Icecave\Manifold\Connection\Container\ConnectionPool(
            'pool1',
            array(
                $connections['slave101'],
                $connections['slave102'],
            )
        ),
        'pool2' => new Icecave\Manifold\Connection\Container\ConnectionPool(
            'pool2',
            array(
                $connections['slave201'],
                $connections['slave202'],
            )
        ),
    );

    $selector = new Icecave\Manifold\Connection\Container\ConnectionContainerSelector(
        new Icecave\Manifold\Connection\Container\ConnectionContainerPair(
            $connections['reporting1'],
            $connectionPools['pool1']
        ),
        array(
            'app_data' => new Icecave\Manifold\Connection\Container\ConnectionContainerPair(
                $connections['master1'],
                $connectionPools['pool1']
            ),
            'app_reporting' => new Icecave\Manifold\Connection\Container\ConnectionContainerPair(
                $connections['reporting2'],
                $connectionPools['pool2']
            ),
            'app_temp' => new Icecave\Manifold\Connection\Container\ConnectionContainerPair(
                $connectionPools['pool2'],
                $connectionPools['pool2']
            ),
            'app_read_only' => new Icecave\Manifold\Connection\Container\ConnectionContainerPair(
                null,
                $connections['master2']
            ),
            'app_write_only' => new Icecave\Manifold\Connection\Container\ConnectionContainerPair(
                $connections['master2'],
                null
            ),
        )
    );

    $replicationTrees = array();
    $replicationTree = new Icecave\Manifold\Replication\ReplicationTree(
        $connections['master1']
    );
    $replicationTree->addSlave(
        $connections['master1'],
        $connections['master2']
    );
    $replicationTree->addSlave(
        $connections['master1'],
        $connections['reporting1']
    );
    $replicationTree->addSlave(
        $connections['reporting1'],
        $connections['slave101']
    );
    $replicationTree->addSlave(
        $connections['reporting1'],
        $connections['slave102']
    );
    $replicationTree->addSlave(
        $connections['master1'],
        $connections['reporting2']
    );
    $replicationTree->addSlave(
        $connections['reporting2'],
        $connections['slave201']
    );
    $replicationTree->addSlave(
        $connections['reporting2'],
        $connections['slave202']
    );
    $replicationTree->addSlave(
        $connections['master1'],
        $connections['reporting3']
    );
    $replicationTree->addSlave(
        $connections['reporting3'],
        $connections['slave301']
    );
    $replicationTree->addSlave(
        $connections['reporting3'],
        $connections['slave302']
    );
    $replicationTrees[] = $replicationTree;
    $replicationTree = new Icecave\Manifold\Replication\ReplicationTree(
        $connections['master3']
    );
    $replicationTrees[] = $replicationTree;

    return new Icecave\Manifold\Configuration\Configuration(
        $connections,
        $connectionPools,
        $selector,
        $replicationTrees
    );
}
EOD;
        $actual = $this->generator->generate($configuration);

        $this->assertSame($expected, $actual);

        eval('$actualConfiguration = call_user_func(' . $actual . ');');

        $this->assertInstanceOf('Icecave\Manifold\Configuration\Configuration', $actualConfiguration);
        $actualConnections = $actualConfiguration->connections();
        $this->assertInstanceOf('Icecave\Manifold\Connection\LazyConnection', $actualConnections['master1']);
        $actualConnectionPools = $actualConfiguration->connectionPools();
        $this->assertInstanceOf(
            'Icecave\Manifold\Connection\Container\ConnectionPool',
            $actualConnectionPools['pool1']
        );
        $this->assertSame(5, count($actualConfiguration->connectionContainerSelector()->databases()));
        $this->assertSame(2, count($actualConfiguration->replicationTrees()));
    }
}
