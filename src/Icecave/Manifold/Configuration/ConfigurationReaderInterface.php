<?php
namespace Icecave\Manifold\Configuration;

/**
 * The interface implemented by configuration readers.
 */
interface ConfigurationReaderInterface
{
    /**
     * Read configuration from a file.
     *
     * @param string      $path     The path to the file.
     * @param string|null $mimeType The mime type of the configuration data.
     *
     * @return ConfigurationInterface The parsed configuration.
     */
    public function readFile($path, $mimeType = null);

    /**
     * Read configuration from a string.
     *
     * @param string      $data     The configuration data.
     * @param string|null $mimeType The mime type of the configuration data.
     *
     * @return ConfigurationInterface The parsed configuration.
     */
    public function readString($data, $mimeType = null);
}
