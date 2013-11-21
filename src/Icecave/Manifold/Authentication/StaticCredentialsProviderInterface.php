<?php
namespace Icecave\Manifold\Authentication;

use Icecave\Collections\Map;

/**
 * The interface implemented by static credentials providers.
 */
interface StaticCredentialsProviderInterface extends
    CredentialsProviderInterface
{
    /**
     * Get the default credentials.
     *
     * @return CredentialsInterface The default credentials.
     */
    public function defaultCredentials();

    /**
     * Get the connection credential map.
     *
     * @return Map<string,CredentialsInterface> The connection credential map.
     */
    public function connectionCredentials();
}
