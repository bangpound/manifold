<?php
namespace Icecave\Manifold\Configuration;

use Eloquent\Schemer\Constraint\Reader\SchemaReader;
use Eloquent\Schemer\Loader\ContentType;
use Eloquent\Schemer\Reader\ReaderInterface;
use Eloquent\Schemer\Reader\ValidatingReader;
use Eloquent\Schemer\Validation\BoundConstraintValidator;
use Eloquent\Schemer\Value\ArrayValue;
use Eloquent\Schemer\Value\NullValue;
use Eloquent\Schemer\Value\ObjectValue;
use Eloquent\Schemer\Value\ValueInterface;
use Icecave\Collections\Map;
use Icecave\Collections\Vector;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Connection\ConnectionFactoryInterface;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPool;
use Icecave\Manifold\Connection\Pool\ConnectionPoolInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolPair;
use Icecave\Manifold\Connection\Pool\ConnectionPoolPairInterface;
use Icecave\Manifold\Connection\Pool\ConnectionPoolSelector;
use Icecave\Manifold\Connection\Pool\ConnectionPoolSelectorInterface;
use Icecave\Manifold\Replication\ReplicationTree;
use Icecave\Manifold\Replication\ReplicationTreeInterface;

/**
 * Reads configuration from files and strings.
 */
class ConfigurationReader implements ConfigurationReaderInterface
{
    /**
     * Construct a new configuration reader.
     *
     * @param ReaderInterface|null            $reader                   The internal reader to use.
     * @param ConnectionFactoryInterface|null $defaultConnectionFactory The default connection factory to use.
     */
    public function __construct(
        ReaderInterface $reader = null,
        ConnectionFactoryInterface $defaultConnectionFactory = null
    ) {
        if (null === $defaultConnectionFactory) {
            $defaultConnectionFactory = new ConnectionFactory;
        }

        $this->reader = $reader;
        $this->defaultConnectionFactory = $defaultConnectionFactory;
    }

    /**
     * Get the internal reader.
     *
     * @return ReaderInterface The internal reader.
     */
    public function reader()
    {
        if (null === $this->reader) {
            $schemaReader = new SchemaReader;
            $schema = $schemaReader->readPath(
                __DIR__ .
                    '/../../../../res/schema/manifold-configuration-schema.yml'
            );

            $this->reader = new ValidatingReader(
                new BoundConstraintValidator($schema)
            );
        }

        return $this->reader;
    }

    /**
     * Get the default connection factory.
     *
     * @return ConnectionFactoryInterface The default connection factory.
     */
    public function defaultConnectionFactory()
    {
        return $this->defaultConnectionFactory;
    }

    /**
     * Read configuration from a file.
     *
     * @param string                          $path              The path to the file.
     * @param string|null                     $mimeType          The mime type of the configuration data.
     * @param ConnectionFactoryInterface|null $connectionFactory The connection factory to use.
     *
     * @return ConfigurationInterface The parsed configuration.
     */
    public function readFile(
        $path,
        $mimeType = null,
        ConnectionFactoryInterface $connectionFactory = null
    ) {
        if (null === $mimeType) {
            $mimeType = ContentType::YAML()->primaryMimeType();
        }

        return $this->createConfiguration(
            $this->reader()->readPath($path, $mimeType),
            $connectionFactory
        );
    }

    /**
     * Read configuration from a string.
     *
     * @param string                          $data              The configuration data.
     * @param string|null                     $mimeType          The mime type of the configuration data.
     * @param ConnectionFactoryInterface|null $connectionFactory The connection factory to use.
     *
     * @return ConfigurationInterface The parsed configuration.
     */
    public function readString(
        $data,
        $mimeType = null,
        ConnectionFactoryInterface $connectionFactory = null
    ) {
        if (null === $mimeType) {
            $mimeType = ContentType::YAML()->primaryMimeType();
        }

        return $this->createConfiguration(
            $this->reader()->readString($data, $mimeType),
            $connectionFactory
        );
    }

    /**
     * Builds a new Manifold configuration instance from raw configuration data.
     *
     * @param ObjectValue                     $value             The raw configuration data.
     * @param ConnectionFactoryInterface|null $connectionFactory The connection factory to use.
     *
     * @return ConfigurationInterface The newly built configuration instance.
     */
    protected function createConfiguration(
        ObjectValue $value,
        ConnectionFactoryInterface $connectionFactory = null
    ) {
        list($connections, $defaultConnection) = $this->createConnections(
            $value,
            $connectionFactory
        );

        $pools = $this->createPools($value, $connections);
        $selector = $this->createSelector(
            $value,
            $connections,
            $pools,
            $defaultConnection
        );
        $replicationTree = $this->createReplicationTrees(
            $value,
            $connections,
            $pools,
            $defaultConnection
        );

        return new Configuration(
            $connections,
            $pools,
            $selector,
            $replicationTree
        );
    }

    /**
     * Creates a map of connections from raw configuration data.
     *
     * @param ObjectValue                     $value             The raw configuration data.
     * @param ConnectionFactoryInterface|null $connectionFactory The connection factory to use.
     *
     * @return tuple<Map<string,ConnectionInterface>,ConnectionInterface> A tuple of the connection map, and the default connection.
     */
    protected function createConnections(
        ObjectValue $value,
        ConnectionFactoryInterface $connectionFactory = null
    ) {
        if (null === $connectionFactory) {
            $connectionFactory = $this->defaultConnectionFactory();
        }

        $connections = new Map;
        $defaultConnection = null;
        foreach ($value->get('connections') as $name => $options) {
            $connection = $connectionFactory->create(
                $name,
                $this->stringOrVariable($options->getRaw('dsn')),
                $this->stringOrVariable($options->getRawDefault('username')),
                $this->stringOrVariable($options->getRawDefault('password'))
            );
            $connections->add($name, $connection);

            if (null === $defaultConnection) {
                $defaultConnection = $connection;
            }
        }

        return array($connections, $defaultConnection);
    }

    /**
     * Creates a map of connection pools from raw configuration data.
     *
     * @param ObjectValue                     $value       The raw configuration data.
     * @param Map<string,ConnectionInterface> $connections The connection map.
     *
     * @return Map<string,ConnectionPoolInterface> The connection pool map.
     */
    protected function createPools(
        ObjectValue $value,
        Map $connections
    ) {
        $pools = new Map;
        foreach ($value->get('pools') as $poolName => $connectionNames) {
            $pools->add(
                $poolName,
                $this->createPool($poolName, $connectionNames, $connections)
            );
        }

        return $pools;
    }

    /**
     * Creates a new connection pool selector from raw configuration data.
     *
     * @param ObjectValue                         $value             The raw configuration data.
     * @param Map<string,ConnectionInterface>     $connections       The connection map.
     * @param Map<string,ConnectionPoolInterface> $pools             The connection pool map.
     * @param ConnectionInterface                 $defaultConnection The default connection.
     *
     * @return ConnectionPoolSelectorInterface The new connection pool selector.
     */
    protected function createSelector(
        ObjectValue $value,
        Map $connections,
        Map $pools,
        ConnectionInterface $defaultConnection
    ) {
        $databases = new Map;
        $defaultPool = $this->createSingleConnectionPool($defaultConnection);
        $defaultPair = null;

        $selection = $value->get('selection');
        if ($selection->has('default')) {
            $defaultPair = $this->createConnectionPoolPair(
                $selection->get('default'),
                $connections,
                $pools,
                $defaultPool
            );
        }
        if (null === $defaultPair) {
            $defaultPair = new ConnectionPoolPair($defaultPool, $defaultPool);
        }

        foreach ($selection->get('databases') as $name => $pair) {
            $databases->add(
                $name,
                $this->createConnectionPoolPair($pair, $connections, $pools)
            );
        }

        return new ConnectionPoolSelector($defaultPair, $databases);
    }

    /**
     * Creates a vector of replication trees from raw configuration data.
     *
     * @param ObjectValue                         $value             The raw configuration data.
     * @param Map<string,ConnectionInterface>     $connections       The connection map.
     * @param Map<string,ConnectionPoolInterface> $pools             The connection pool map.
     * @param ConnectionInterface                 $defaultConnection The default connection.
     *
     * @return Vector<ReplicationTreeInterface> The new replication trees.
     */
    protected function createReplicationTrees(
        ObjectValue $value,
        Map $connections,
        Map $pools,
        ConnectionInterface $defaultConnection
    ) {
        $replicationTrees = new Vector;

        foreach ($value->get('replication') as $name => $treeNodes) {
            $masterConnection = $this->findConnection($name, $connections);
            $replicationTree = new ReplicationTree($masterConnection);

            $this->addReplicationNodes(
                $treeNodes,
                $connections,
                $pools,
                $replicationTree,
                $masterConnection
            );

            $replicationTrees->pushBack($replicationTree);
        }

        if ($replicationTrees->count() < 1) {
            $replicationTrees->pushBack(
                new ReplicationTree($defaultConnection)
            );
        }

        return $replicationTrees;
    }

    /**
     * Creates a new connection pool from raw configuration data.
     *
     * @param string                          $poolName        The connection pool name.
     * @param ArrayValue                      $connectionNames The raw configuration data.
     * @param Map<string,ConnectionInterface> $connections     The connection map.
     *
     * @return ConnectionPoolInterface The new connection pool.
     */
    protected function createPool(
        $poolName,
        ArrayValue $connectionNames,
        Map $connections
    ) {
        $poolConnections = new Vector;
        foreach ($connectionNames as $connectionName) {
            $poolConnections->pushBack(
                $this->findConnection($connectionName->value(), $connections)
            );
        }

        return new ConnectionPool($poolName, $poolConnections);
    }

    /**
     * Creates a new read/write connection pool pair from raw configuration
     * data.
     *
     * @param ObjectValue                         $value       The raw configuration data.
     * @param Map<string,ConnectionInterface>     $connections The connection map.
     * @param Map<string,ConnectionPoolInterface> $pools       The connection pool map.
     * @param ConnectionPoolInterface|null        $defaultPool The default connection pool.
     *
     * @return ConnectionPoolPairInterface The new read/write pair.
     */
    protected function createConnectionPoolPair(
        ObjectValue $value,
        Map $connections,
        Map $pools,
        ConnectionPoolInterface $defaultPool = null
    ) {
        if ($value->has('write')) {
            $write = $this->findPool(
                $value->getRaw('write'),
                $connections,
                $pools
            );
        } else {
            $write = $defaultPool;
        }

        if ($value->has('read')) {
            $read = $this->findPool(
                $value->getRaw('read'),
                $connections,
                $pools
            );
        } else {
            $read = $defaultPool;
        }

        return new ConnectionPoolPair($write, $read);
    }

    /**
     * Adds replication nodes to an existing tree based upon configuration data.
     *
     * @param ValueInterface                      $treeNodes        The raw configuration data.
     * @param Map<string,ConnectionInterface>     $connections      The connection map.
     * @param Map<string,ConnectionPoolInterface> $pools            The connection pool map.
     * @param ReplicationTreeInterface            $replicationTree  The replication tree to add to.
     * @param ConnectionInterface                 $masterConnection The master connection.
     */
    protected function addReplicationNodes(
        ValueInterface $treeNodes,
        Map $connections,
        Map $pools,
        ReplicationTreeInterface $replicationTree,
        ConnectionInterface $masterConnection
    ) {
        if ($treeNodes instanceof NullValue) {
            return;
        }

        foreach ($treeNodes as $name => $subNodes) {
            $slavePool = $this->findPool($name, $connections, $pools);
            foreach ($slavePool->connections() as $slaveConnection) {
                $replicationTree->addSlave($masterConnection, $slaveConnection);
            }

            if ($connections->hasKey($name)) {
                $this->addReplicationNodes(
                    $subNodes,
                    $connections,
                    $pools,
                    $replicationTree,
                    $this->findConnection($name, $connections)
                );
            }
        }
    }

    /**
     * Finds a connection by name.
     *
     * @param string                          $name        The connection name.
     * @param Map<string,ConnectionInterface> $connections The connection map.
     *
     * @return ConnectionInterface                    The connection.
     * @throws Exception\UndefinedConnectionException If no associated connection is found.
     */
    protected function findConnection($name, Map $connections)
    {
        if ($connections->hasKey($name)) {
            return $connections->get($name);
        }

        throw new Exception\UndefinedConnectionException($name);
    }

    /**
     * Finds a connection pool by name.
     *
     * If the name refers to a connection rather than a pool, the connection is
     * placed in a pool by itself and returned.
     *
     * @param string                              $name        The pool or connection name.
     * @param Map<string,ConnectionInterface>     $connections The connection map.
     * @param Map<string,ConnectionPoolInterface> $pools       The connection pool map.
     *
     * @return ConnectionPoolInterface                The connection pool.
     * @throws Exception\UndefinedConnectionException If no associated connection or pool is found.
     */
    protected function findPool($name, Map $connections, Map $pools)
    {
        if ($pools->hasKey($name)) {
            return $pools->get($name);
        }

        return $this->createSingleConnectionPool(
            $this->findConnection($name, $connections)
        );
    }

    /**
     * Wraps a single connection in its own pool.
     *
     * @param ConnectionInterface $connection The connection to wrap.
     *
     * @return ConnectionPoolInterface The new connection pool.
     */
    protected function createSingleConnectionPool(ConnectionInterface $connection)
    {
        $connections = new Vector;
        $connections->pushBack($connection);

        return new ConnectionPool($connection->name(), $connections);
    }

    /**
     * Look for environment variable names in the supplied string value, and
     * return an environment variable instead of a string if found.
     *
     * @param string $string The string to inspect.
     *
     * @return string|EnvironmentVariableInterface The resulting value.
     */
    protected function stringOrVariable($string)
    {
        if (null === $string) {
            return null;
        }

        $firstCharacter = $string[0];
        if ('$' === $firstCharacter) {
            return new EnvironmentVariable(substr($string, 1));
        }
        if ('\\' === $firstCharacter) {
            return substr($string, 1);
        }

        return $string;
    }

    private $reader;
    private $defaultConnectionFactory;
}
