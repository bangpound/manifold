<?php
namespace Icecave\Manifold\Authentication\Caching;

use Icecave\Manifold\Authentication\Exception\CredentialsReadException;
use Icecave\Manifold\Authentication\StaticCredentialsProviderInterface;

/**
 * The interface implemented by credentials provider cache file generators.
 */
interface CredentialsCacheFileGeneratorInterface
{
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
    );

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
    );

    /**
     * Generate the default path for the cached version of the supplied
     * credentials path.
     *
     * @param string $credentialsPath The path to the credentials file.
     *
     * @return string The path to the default cache file.
     */
    public function defaultCachePath($credentialsPath);
}
