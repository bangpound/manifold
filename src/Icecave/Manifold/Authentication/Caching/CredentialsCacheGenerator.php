<?php
namespace Icecave\Manifold\Authentication\Caching;

use Icecave\Manifold\Authentication\CredentialsInterface;
use Icecave\Manifold\Authentication\StaticCredentialsProviderInterface;

/**
 * Generates cacheable PHP code from pre-validated credentials providers.
 */
class CredentialsCacheGenerator implements CredentialsCacheGeneratorInterface
{
    /**
     * Generate PHP code to recreate the supplied credentials provider.
     *
     * The code generated by this method is intended to be stored in a file that
     * will be cached by the PHP opcode cache, effectively bypassing the need to
     * validate the credentials file each time.
     *
     * @param StaticCredentialsProviderInterface $provider The credentials provider to generate code for.
     *
     * @return string PHP source code that will replicate the supplied credentials provider.
     */
    public function generate(StaticCredentialsProviderInterface $provider)
    {
        return sprintf(
            "function () {\n%s}",
            $this->indent($this->generateBody($provider))
        );
    }

    /**
     * Generate the function body portion of the code.
     *
     * @param StaticCredentialsProviderInterface $provider The credentials provider to generate code for.
     *
     * @return string The closure source code.
     */
    protected function generateBody(
        StaticCredentialsProviderInterface $provider
    ) {
        return sprintf("return %s;\n", $this->generateProvider($provider));
    }

    /**
     * Generate the credentials provider creation code.
     *
     * @param StaticCredentialsProviderInterface $provider The credentials provider to generate code for.
     *
     * @return string The credentials provider creation source code.
     */
    protected function generateProvider(
        StaticCredentialsProviderInterface $provider
    ) {
        return sprintf(
            "new %s(\n%s,\n%s\n)",
            'Icecave\Manifold\Authentication\CredentialsProvider',
            $this->indent(
                $this->generateCredentials($provider->defaultCredentials())
            ),
            $this->indent($this->generateConnectionCredentials($provider))
        );
    }

    /**
     * Generate the connection credentials creation code.
     *
     * @param StaticCredentialsProviderInterface $provider The credentials provider to generate code for.
     *
     * @return string The connection credentials creation source code.
     */
    protected function generateConnectionCredentials(
        StaticCredentialsProviderInterface $provider
    ) {
        if (count($provider->connectionCredentials()) < 1) {
            return 'array()';
        }

        $connectionCredentials = '';

        foreach ($provider->connectionCredentials() as $name => $credentials) {
            $connectionCredentials .= sprintf(
                "%s => %s,\n",
                var_export($name, true),
                $this->generateCredentials($credentials)
            );
        }

        return sprintf(
            "array(\n%s)",
            $this->indent($connectionCredentials)
        );
    }

    /**
     * Generate creation code for the supplied credentials.
     *
     * @param CredentialsInterface $credentials The credentials to generate code for.
     *
     * @return string The credentials creation source code.
     */
    protected function generateCredentials(CredentialsInterface $credentials)
    {
        return sprintf(
            "new Icecave\Manifold\Authentication\Credentials(\n%s,\n%s\n)",
            $this->indent(
                null === $credentials->username() ?
                    'null' :
                    var_export($credentials->username(), true)
            ),
            $this->indent(
                null === $credentials->password() ?
                    'null' :
                    var_export($credentials->password(), true)
            )
        );
    }

    /**
     * Indent the supplied source code.
     *
     * @param string       $source The source code to indent.
     * @param integer|null $levels The number of levels of indentation.
     *
     * @return string The indented source code.
     */
    protected function indent($source, $levels = null)
    {
        if (null === $levels) {
            $levels = 1;
        }

        $indent = str_repeat('    ', $levels);

        return implode(
            "\n",
            array_map(
                function ($line) use ($indent) {
                    if ('' === $line) {
                        return '';
                    }

                    return $indent . $line;
                },
                explode("\n", $source)
            )
        );
    }
}