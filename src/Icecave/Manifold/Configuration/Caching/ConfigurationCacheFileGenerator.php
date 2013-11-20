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
 * Generates configuration cache files.
 */
class ConfigurationCacheFileGenerator implements
    ConfigurationCacheFileGeneratorInterface
{
    /**
     * Construct a new configuration cache file generator.
     *
     * @param ConfigurationReaderInterface|null         $reader    The reader to use.
     * @param ConfigurationCacheGeneratorInterface|null $generator The generator to use.
     * @param Isolator|null                             $isolator  The isolator to use.
     */
    public function __construct(
        ConfigurationReaderInterface $reader = null,
        ConfigurationCacheGeneratorInterface $generator = null,
        Isolator $isolator = null
    ) {
        if (null === $reader) {
            $reader = new ConfigurationReader;
        }
        if (null === $generator) {
            $generator = new ConfigurationCacheGenerator;
        }

        $this->reader = $reader;
        $this->generator = $generator;
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
     * Get the internal generator.
     *
     * @return ConfigurationCacheGeneratorInterface The generator.
     */
    public function generator()
    {
        return $this->generator;
    }

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
    ) {
        if (null === $cachePath) {
            $cachePath = $this->defaultCachePath($configurationPath);
        }

        return $this->generateForConfiguration(
            $this->reader()->readFile(
                $configurationPath,
                $mimeType,
                $connectionFactory
            ),
            $cachePath
        );
    }

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
    ) {
        try {
            $this->isolator()->file_put_contents(
                $cachePath,
                sprintf(
                    "<?php\n\nreturn %s;\n",
                    $this->generator()->generate($configuration)
                )
            );
        } catch (ErrorException $e) {
            throw new Exception\ConfigurationCacheWriteException(
                $cachePath,
                $e
            );
        }
    }

    /**
     * Generate the default path for the cached version of the supplied
     * configuration path.
     *
     * @param string $configurationPath The path to the configuration file.
     *
     * @return string The path to the default cache file.
     */
    public function defaultCachePath($configurationPath)
    {
        return $configurationPath . '.cache.php';
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

    private $generator;
    private $isolator;
}
