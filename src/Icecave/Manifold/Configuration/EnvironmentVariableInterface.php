<?php
namespace Icecave\Manifold\Configuration;

/**
 * The interface implemented by environment variable placeholders.
 */
interface EnvironmentVariableInterface
{
    /**
     * Get the environment variable name.
     *
     * @return string The environment variable name.
     */
    public function name();

    /**
     * Get the value of this environment variable.
     *
     * @return string                                          The environment variable value.
     * @throws Exception\UndefinedEnvironmentVariableException If the environment variable is undefined.
     */
    public function value();

    /**
     * Get the value of this environment variable.
     *
     * @return string The environment variable value.
     */
    public function __toString();
}
