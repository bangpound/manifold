<?php
namespace Icecave\Manifold\Configuration\Caching;

use ErrorException;
use Icecave\Isolator\Isolator;
use Icecave\Manifold\Configuration\ConfigurationInterface;
use Icecave\Manifold\Configuration\ConfigurationReader;
use Icecave\Manifold\Configuration\ConfigurationReaderInterface;
use Icecave\Manifold\Configuration\Exception\ConfigurationReadException;
use Icecave\Manifold\Connection\ConnectionFactoryInterface;

/**
 * A configuration reader that utilizes configuration caches.
 */
class CachingConfigurationReader implements ConfigurationReaderInterface
{
    /**
     * Construct a new caching configuration reader.
     *
     * @param ConfigurationReaderInterface|null             $reader    The reader to use.
     * @param ConfigurationCacheFileGeneratorInterface|null $generator The generator to use.
     * @param Isolator|null                                 $isolator  The isolator to use.
     */
    public function __construct(
        ConfigurationReaderInterface $reader = null,
        ConfigurationCacheFileGeneratorInterface $generator = null,
        Isolator $isolator = null
    ) {
        if (null === $reader) {
            $reader = new ConfigurationReader();
        }
        if (null === $generator) {
            $generator = new ConfigurationCacheFileGenerator($reader);
        }

        $this->generator = $generator;
        $this->reader = $reader;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * Get the reader.
     *
     * @return ConfigurationReaderInterface The reader.
     */
    public function reader()
    {
        return $this->reader;
    }

    /**
     * Get the generator.
     *
     * @return ConfigurationCacheFileGeneratorInterface The generator.
     */
    public function generator()
    {
        return $this->generator;
    }

    /**
     * Read configuration from a file.
     *
     * @param string                          $path              The path to the file.
     * @param string|null                     $mimeType          The mime type of the configuration data.
     * @param ConnectionFactoryInterface|null $connectionFactory The connection factory to use.
     *
     * @return ConfigurationInterface     The parsed configuration.
     * @throws ConfigurationReadException If the file cannot be read.
     */
    public function readFile(
        $path,
        $mimeType = null,
        ConnectionFactoryInterface $connectionFactory = null
    ) {
        $cachePath = $this->generator()->defaultCachePath($path);

        try {
            $configurationFactory = $this->isolator()->include($cachePath);
            $configuration = $configurationFactory($connectionFactory);
        } catch (ErrorException $e) {
            $configuration = $this->reader()
                ->readFile($path, $mimeType, $connectionFactory);
            $this->generator()->generateForConfiguration(
                $configuration,
                $cachePath
            );
        }

        return $configuration;
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
        return $this->reader()
            ->readString($data, $mimeType, $connectionFactory);
    }

    /**
     * Get the isolator.
     *
     * @return Isolator The isolator.
     */
    protected function isolator()
    {
        return $this->isolator;
    }

    private $reader;
    private $generator;
    private $isolator;
}
