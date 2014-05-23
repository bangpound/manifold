<?php
namespace Icecave\Manifold\Authentication;

use Eloquent\Schemer\Constraint\Reader\SchemaReader;
use Eloquent\Schemer\Loader\ContentType;
use Eloquent\Schemer\Loader\Exception\LoadException;
use Eloquent\Schemer\Reader\ReaderInterface;
use Eloquent\Schemer\Reader\ValidatingReader;
use Eloquent\Schemer\Validation\BoundConstraintValidator;
use Eloquent\Schemer\Value\ObjectValue;
use Icecave\Isolator\Isolator;

/**
 * Reads credentials from files and strings.
 */
class CredentialsReader implements CredentialsReaderInterface
{
    /**
     * Construct a new credentials reader.
     *
     * @param ReaderInterface|null $reader   The internal reader to use.
     * @param Isolator|null        $isolator The isolator to use.
     */
    public function __construct(
        ReaderInterface $reader = null,
        Isolator $isolator = null
    ) {
        $this->reader = $reader;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * Get the internal reader.
     *
     * @return ReaderInterface The internal reader.
     */
    // @codeCoverageIgnoreStart
    public function reader()
    {
        // @codeCoverageIgnoreEnd
        if (null === $this->reader) {
            $schemaReader = new SchemaReader;
            $schema = $schemaReader->readPath(
                __DIR__ . '/../../res/schema/manifold-credentials-schema.yml'
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
     * @return CredentialsProviderInterface       The parsed credentials as a credentials provider.
     * @throws Exception\CredentialsReadException If the file cannot be read.
     */
    public function readFile($path, $mimeType = null)
    {
        if (null === $mimeType) {
            $mimeType = ContentType::YAML()->primaryMimeType();
        }

        try {
            $data = $this->reader()->readPath(
                $this->isolator()->realpath($path),
                $mimeType
            );
        } catch (LoadException $e) {
            throw new Exception\CredentialsReadException($path, $e);
        }

        return $this->createProvider($data);
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

        $connectionCredentials = array();
        if ($value->has('connections')) {
            foreach ($value->get('connections') as $name => $subValue) {
                $connectionCredentials[$name] =
                    $this->createCredentials($subValue);
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
    private $isolator;
}
