<?php
namespace Icecave\Manifold\Authentication\Caching;

use ErrorException;
use Icecave\Isolator\Isolator;
use Icecave\Manifold\Authentication\CredentialsReader;
use Icecave\Manifold\Authentication\CredentialsReaderInterface;
use Icecave\Manifold\Authentication\Exception\CredentialsReadException;
use Icecave\Manifold\Authentication\StaticCredentialsProviderInterface;

/**
 * Generates credentials provider cache files.
 */
class CredentialsCacheFileGenerator implements
    CredentialsCacheFileGeneratorInterface
{
    /**
     * Construct a new credentials provider cache file generator.
     *
     * @param CredentialsReaderInterface|null         $reader    The reader to use.
     * @param CredentialsCacheGeneratorInterface|null $generator The generator to use.
     * @param Isolator|null                           $isolator  The isolator to use.
     */
    public function __construct(
        CredentialsReaderInterface $reader = null,
        CredentialsCacheGeneratorInterface $generator = null,
        Isolator $isolator = null
    ) {
        if (null === $reader) {
            $reader = new CredentialsReader();
        }
        if (null === $generator) {
            $generator = new CredentialsCacheGenerator();
        }

        $this->reader = $reader;
        $this->generator = $generator;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * Get the reader.
     *
     * @return CredentialsReaderInterface The reader.
     */
    public function reader()
    {
        return $this->reader;
    }

    /**
     * Get the internal generator.
     *
     * @return CredentialsCacheGeneratorInterface The generator.
     */
    public function generator()
    {
        return $this->generator;
    }

    /**
     * Generate a credentials provider cache file for the credentials file found
     * at the supplied path.
     *
     * @param string      $credentialsPath The path to the credentials file to cache.
     * @param string|null $cachePath       The path for the generated cache file.
     * @param string|null $mimeType        The mime type of the credentials data.
     *
     * @return string                                   The path for the generated cache file.
     * @throws CredentialsReadException                 If the credentials file cannot be read.
     * @throws Exception\CredentialsCacheWriteException If the cache file cannot be written.
     */
    public function generate(
        $credentialsPath,
        $cachePath = null,
        $mimeType = null
    ) {
        if (null === $cachePath) {
            $cachePath = $this->defaultCachePath($credentialsPath);
        }

        return $this->generateForProvider(
            $this->reader()->readFile($credentialsPath, $mimeType),
            $cachePath
        );
    }

    /**
     * Generate a credentials provider cache file for the supplied credentials
     * provider.
     *
     * @param StaticCredentialsProviderInterface $provider  The credentials provider to generate the cache file for.
     * @param string                             $cachePath The path for the generated cache file.
     *
     * @return string                                   The path for the generated cache file.
     * @throws Exception\CredentialsCacheWriteException If the cache file cannot be written.
     */
    public function generateForProvider(
        StaticCredentialsProviderInterface $provider,
        $cachePath
    ) {
        try {
            $this->isolator()->file_put_contents(
                $cachePath,
                sprintf(
                    "<?php\n\nreturn %s;\n",
                    $this->generator()->generate($provider)
                )
            );
        } catch (ErrorException $e) {
            throw new Exception\CredentialsCacheWriteException($cachePath, $e);
        }
    }

    /**
     * Generate the default path for the cached version of the supplied
     * credentials path.
     *
     * @param string $credentialsPath The path to the credentials file.
     *
     * @return string The path to the default cache file.
     */
    public function defaultCachePath($credentialsPath)
    {
        return $credentialsPath . '.cache.php';
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
