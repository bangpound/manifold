<?php
namespace Icecave\Manifold\Configuration;

use Eloquent\Schemer\Constraint\Reader\SchemaReader;
use Eloquent\Schemer\Loader\ContentType;
use Eloquent\Schemer\Loader\Exception\LoadException;
use Eloquent\Schemer\Reader\ReaderInterface;
use Eloquent\Schemer\Reader\ValidatingReader;
use Eloquent\Schemer\Validation\BoundConstraintValidator;
use Eloquent\Schemer\Value\ArrayValue;
use Eloquent\Schemer\Value\NullValue;
use Eloquent\Schemer\Value\ObjectValue;
use Eloquent\Schemer\Value\ValueInterface;
use Icecave\Collections\Map;
use Icecave\Manifold\Connection\ConnectionFactory;
use Icecave\Manifold\Connection\ConnectionFactoryInterface;
use Icecave\Manifold\Connection\ConnectionInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerPair;
use Icecave\Manifold\Connection\Container\ConnectionContainerPairInterface;
use Icecave\Manifold\Connection\Container\ConnectionContainerSelector;
use Icecave\Manifold\Connection\Container\ConnectionContainerSelectorInterface;
use Icecave\Manifold\Connection\Container\ConnectionPool;
use Icecave\Manifold\Connection\Container\ConnectionPoolInterface;
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
     * @return ConfigurationInterface               The parsed configuration.
     * @throws Exception\ConfigurationReadException If the file cannot be read.
     */
    public function readFile(
        $path,
        $mimeType = null,
        ConnectionFactoryInterface $connectionFactory = null
    ) {
        if (null === $mimeType) {
            $mimeType = ContentType::YAML()->primaryMimeType();
        }

        try {
            $data = $this->reader()->readPath($path, $mimeType);
        } catch (LoadException $e) {
            throw new Exception\ConfigurationReadException($path, $e);
        }

        return $this->createConfiguration($data, $connectionFactory);
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
        foreach ($value->get('connections') as $name => $dsn) {
            $connection = $connectionFactory->create(
                $name,
                $dsn->value()
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
     * @return array<string,ConnectionPoolInterface> The connection pool map.
     */
    protected function createPools(
        ObjectValue $value,
        Map $connections
    ) {
        $pools = array();
        foreach ($value->get('pools') as $poolName => $connectionNames) {
            $pools[$poolName] =
                $this->createPool($poolName, $connectionNames, $connections);
        }

        return $pools;
    }

    /**
     * Creates a new connection container selector from raw configuration data.
     *
     * @param ObjectValue                           $value             The raw configuration data.
     * @param Map<string,ConnectionInterface>       $connections       The connection map.
     * @param array<string,ConnectionPoolInterface> $pools             The connection pool map.
     * @param ConnectionInterface                   $defaultConnection The default connection.
     *
     * @return ConnectionContainerSelectorInterface The new connection container selector.
     */
    protected function createSelector(
        ObjectValue $value,
        Map $connections,
        array $pools,
        ConnectionInterface $defaultConnection
    ) {
        $databases = array();
        $defaultPair = null;

        $selection = $value->get('selection');
        if ($selection->has('default')) {
            $defaultPair = $this->createConnectionContainerPair(
                $selection->get('default'),
                $connections,
                $pools,
                $defaultConnection
            );
        }
        if (null === $defaultPair) {
            $defaultPair = new ConnectionContainerPair(
                $defaultConnection,
                $defaultConnection
            );
        }

        foreach ($selection->get('databases') as $name => $pair) {
            $databases[$name] = $this->createConnectionContainerPair(
                $pair,
                $connections,
                $pools
            );
        }

        return new ConnectionContainerSelector($defaultPair, $databases);
    }

    /**
     * Creates an array of replication trees from raw configuration data.
     *
     * @param ObjectValue                           $value             The raw configuration data.
     * @param Map<string,ConnectionInterface>       $connections       The connection map.
     * @param array<string,ConnectionPoolInterface> $pools             The connection pool map.
     * @param ConnectionInterface                   $defaultConnection The default connection.
     *
     * @return array<ReplicationTreeInterface> The new replication trees.
     */
    protected function createReplicationTrees(
        ObjectValue $value,
        Map $connections,
        array $pools,
        ConnectionInterface $defaultConnection
    ) {
        $replicationTrees = array();

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

            $replicationTrees[] = $replicationTree;
        }

        if (count($replicationTrees) < 1) {
            $replicationTrees[] = new ReplicationTree($defaultConnection);
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
        $poolConnections = array();
        foreach ($connectionNames as $connectionName) {
            $poolConnections[] =
                $this->findConnection($connectionName->value(), $connections);
        }

        return new ConnectionPool($poolName, $poolConnections);
    }

    /**
     * Creates a new read/write connection container pair from raw configuration
     * data.
     *
     * @param ObjectValue                           $value            The raw configuration data.
     * @param Map<string,ConnectionInterface>       $connections      The connection map.
     * @param array<string,ConnectionPoolInterface> $pools            The connection pool map.
     * @param ConnectionContainerInterface|null     $defaultContainer The default connection container.
     *
     * @return ConnectionContainerPairInterface The new read/write pair.
     */
    protected function createConnectionContainerPair(
        ObjectValue $value,
        Map $connections,
        array $pools,
        ConnectionContainerInterface $defaultContainer = null
    ) {
        if ($value->has('write')) {
            $write = $this->findContainer(
                $value->getRaw('write'),
                $connections,
                $pools
            );
        } else {
            $write = $defaultContainer;
        }

        if ($value->has('read')) {
            $read = $this->findContainer(
                $value->getRaw('read'),
                $connections,
                $pools
            );
        } else {
            $read = $defaultContainer;
        }

        return new ConnectionContainerPair($write, $read);
    }

    /**
     * Adds replication nodes to an existing tree based upon configuration data.
     *
     * @param ValueInterface                        $treeNodes        The raw configuration data.
     * @param Map<string,ConnectionInterface>       $connections      The connection map.
     * @param array<string,ConnectionPoolInterface> $pools            The connection pool map.
     * @param ReplicationTreeInterface              $replicationTree  The replication tree to add to.
     * @param ConnectionInterface                   $masterConnection The master connection.
     */
    protected function addReplicationNodes(
        ValueInterface $treeNodes,
        Map $connections,
        array $pools,
        ReplicationTreeInterface $replicationTree,
        ConnectionInterface $masterConnection
    ) {
        if ($treeNodes instanceof NullValue) {
            return;
        }

        foreach ($treeNodes as $name => $subNodes) {
            $slaveContainer = $this->findContainer($name, $connections, $pools);
            foreach ($slaveContainer->connections() as $slaveConnection) {
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
     * Finds a connection container by name.
     *
     * If the name refers to a connection rather than a pool, the connection is
     * placed in a pool by itself and returned.
     *
     * @param string                                $name        The pool or connection name.
     * @param Map<string,ConnectionInterface>       $connections The connection map.
     * @param array<string,ConnectionPoolInterface> $pools       The connection pool map.
     *
     * @return ConnectionContainerInterface           The connection container.
     * @throws Exception\UndefinedConnectionException If no associated connection or pool is found.
     */
    protected function findContainer($name, Map $connections, array $pools)
    {
        if (array_key_exists($name, $pools)) {
            return $pools[$name];
        }

        return $this->findConnection($name, $connections);
    }

    private $reader;
    private $defaultConnectionFactory;
}
