<?php
namespace Icecave\Manifold\Authentication\Caching;

use Icecave\Isolator\Isolator;
use Icecave\Manifold\Authentication\CredentialsProviderInterface;
use Icecave\Manifold\Authentication\CredentialsReader;
use Icecave\Manifold\Authentication\CredentialsReaderInterface;

/**
 * A credentials reader that utilizes credentials provider caches.
 */
class CachingCredentialsReader implements CredentialsReaderInterface
{
    /**
     * Construct a new caching credentials reader.
     *
     * @param CredentialsReaderInterface|null             $reader    The reader to use.
     * @param CredentialsCacheFileGeneratorInterface|null $generator The generator to use.
     * @param Isolator|null                               $isolator  The isolator to use.
     */
    public function __construct(
        CredentialsReaderInterface $reader = null,
        CredentialsCacheFileGeneratorInterface $generator = null,
        Isolator $isolator = null
    ) {
        if (null === $reader) {
            $reader = new CredentialsReader;
        }
        if (null === $generator) {
            $generator = new CredentialsCacheFileGenerator($reader);
        }

        $this->generator = $generator;
        $this->reader = $reader;
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
     * Get the generator.
     *
     * @return CredentialsCacheFileGeneratorInterface The generator.
     */
    public function generator()
    {
        return $this->generator;
    }

    /**
     * Read credentials from a file.
     *
     * @param string      $path     The path to the file.
     * @param string|null $mimeType The mime type of the credentials data.
     *
     * @return CredentialsProviderInterface       The parsed credentials as a credentials provider.
     * @throws Exception\CredentialsReadException If the file cannot be read.
     */
    public function readFile($path, $mimeType = null)
    {
        $cachePath = $this->generator()->defaultCachePath($path);

        if ($this->isolator()->is_file($cachePath)) {
            $providerFactory = $this->isolator()->require($cachePath);
            $provider = $providerFactory();
        } else {
            $provider = $this->reader()->readFile($path, $mimeType);
            $this->generator()->generateForProvider($provider, $cachePath);
        }

        return $provider;
    }

    /**
     * Read credentials from a string.
     *
     * @param string      $data     The credentials data.
     * @param string|null $mimeType The mime type of the credentials data.
     *
     * @return CredentialsProviderInterface The parsed credentials as a credentials provider.
     */
    public function readString($data, $mimeType = null)
    {
        return $this->reader()->readString($data, $mimeType);
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
