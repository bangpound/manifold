<?php
namespace Icecave\Manifold\Authentication;

use Eloquent\Schemer\Constraint\Reader\SchemaReader;
use Eloquent\Schemer\Loader\ContentType;
use Eloquent\Schemer\Reader\ReaderInterface;
use Eloquent\Schemer\Reader\ValidatingReader;
use Eloquent\Schemer\Validation\BoundConstraintValidator;
use Eloquent\Schemer\Value\ObjectValue;
use Icecave\Collections\Map;
use Icecave\Manifold\Authentication\Credentials;

/**
 * Reads credentials from files and strings.
 */
class CredentialsReader implements CredentialsReaderInterface
{
    /**
     * Construct a new credentials reader.
     *
     * @param ReaderInterface|null $reader The internal reader to use.
     */
    public function __construct(ReaderInterface $reader = null)
    {
        $this->reader = $reader;
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
                    '/../../../../res/schema/manifold-credentials-schema.yml'
            );

            $this->reader = new ValidatingReader(
                new BoundConstraintValidator($schema)
            );
        }

        return $this->reader;
    }

    /**
     * Read credentials from a file.
     *
     * @param string      $path     The path to the file.
     * @param string|null $mimeType The mime type of the credentials data.
     *
     * @return CredentialsProviderInterface The parsed credentials as a credentials provider.
     */
    public function readFile($path, $mimeType = null)
    {
        if (null === $mimeType) {
            $mimeType = ContentType::YAML()->primaryMimeType();
        }

        return $this->createProvider(
            $this->reader()->readPath($path, $mimeType)
        );
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
        if (null === $mimeType) {
            $mimeType = ContentType::YAML()->primaryMimeType();
        }

        return $this->createProvider(
            $this->reader()->readString($data, $mimeType)
        );
    }

    /**
     * Create a new credentials provider from raw configuration data.
     *
     * @param ObjectValue $value The raw configuration data.
     *
     * @return CredentialsProviderInterface The newly created credentials provider.
     */
    protected function createProvider(ObjectValue $value)
    {
        if ($value->has('default') && count($value->get('default')) > 0) {
            $defaultCredentials = $this->createCredentials(
                $value->get('default')
            );
        } else {
            $defaultCredentials = new Credentials;
        }

        $connectionCredentials = new Map;
        if ($value->has('connections')) {
            foreach ($value->get('connections') as $name => $subValue) {
                $connectionCredentials->set(
                    $name,
                    $this->createCredentials($subValue)
                );
            }
        }

        return new CredentialsProvider(
            $defaultCredentials,
            $connectionCredentials
        );
    }

    /**
     * Create a new credentials instance from raw configuration data.
     *
     * @param ObjectValue $value The raw configuration data.
     *
     * @return CredentialsInterface The newly created credentials.
     */
    protected function createCredentials(ObjectValue $value)
    {
        return new Credentials(
            $value->getRawDefault('username'),
            $value->getRawDefault('password')
        );
    }

    private $reader;
}
