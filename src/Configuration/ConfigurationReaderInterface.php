<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Manifold\Connection\ConnectionFactoryInterface;

/**
 * The interface implemented by configuration readers.
 */
interface ConfigurationReaderInterface
{
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
    );

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
    );
}
