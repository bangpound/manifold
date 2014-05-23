<?php
namespace Icecave\Manifold\Configuration\Caching;

use Icecave\Manifold\Configuration\ConfigurationInterface;
use Icecave\Manifold\Configuration\Exception\ConfigurationReadException;
use Icecave\Manifold\Connection\ConnectionFactoryInterface;

/**
 * The interface implemented by configuration cache file generators.
 */
interface ConfigurationCacheFileGeneratorInterface
{
    /**
     * Generate a configuration cache file for the configuration found at the
     * supplied path.
     *
     * @param string                          $configurationPath The path to the configuration file to cache.
     * @param string|null                     $cachePath         The path for the generated cache file.
     * @param string|null                     $mimeType          The mime type of the configuration data.
     * @param ConnectionFactoryInterface|null $connectionFactory The connection factory to use.
     *
     * @return string                                     The path for the generated cache file.
     * @throws ConfigurationReadException                 If the configuration file cannot be read.
     * @throws Exception\ConfigurationCacheWriteException If the cache file cannot be written.
     */
    public function generate(
        $configurationPath,
        $cachePath = null,
        $mimeType = null,
        ConnectionFactoryInterface $connectionFactory = null
    );

    /**
     * Generate a configuration cache file for the supplied configuration.
     *
     * @param ConfigurationInterface $configuration The configuration to generate the cache file for.
     * @param string                 $cachePath     The path for the generated cache file.
     *
     * @return string                                     The path for the generated cache file.
     * @throws Exception\ConfigurationCacheWriteException If the cache file cannot be written.
     */
    public function generateForConfiguration(
        ConfigurationInterface $configuration,
        $cachePath
    );

    /**
     * Generate the default path for the cached version of the supplied
     * configuration path.
     *
     * @param string $configurationPath The path to the configuration file.
     *
     * @return string The path to the default cache file.
     */
    public function defaultCachePath($configurationPath);
}
