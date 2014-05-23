<?php
namespace Icecave\Manifold\Authentication;

/**
 * The interface implemented by credentials readers.
 */
interface CredentialsReaderInterface
{
    /**
     * Read credentials from a file.
     *
     * @param string      $path     The path to the file.
     * @param string|null $mimeType The mime type of the credentials data.
     *
     * @return CredentialsProviderInterface       The parsed credentials as a credentials provider.
     * @throws Exception\CredentialsReadException If the file cannot be read.
     */
    public function readFile($path, $mimeType = null);

    /**
     * Read credentials from a string.
     *
     * @param string      $data     The credentials data.
     * @param string|null $mimeType The mime type of the credentials data.
     *
     * @return CredentialsProviderInterface The parsed credentials as a credentials provider.
     */
    public function readString($data, $mimeType = null);
}
