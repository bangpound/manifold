<?php
namespace Icecave\Manifold\Configuration;

use Icecave\Isolator\Isolator;

/**
 * Represents a string value defined as the value of an environment variable at
 * run time.
 */
class EnvironmentVariable implements EnvironmentVariableInterface
{
    /**
     * Construct a new environment variable placeholder.
     *
     * @param string        $name     The environment variable name.
     * @param Isolator|null $isolator The isolator to use.
     */
    public function __construct($name, Isolator $isolator = null)
    {
        $this->name = $name;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * Get the environment variable name.
     *
     * @return string The environment variable name.
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the value of this environment variable.
     *
     * @return string                                          The environment variable value.
     * @throws Exception\UndefinedEnvironmentVariableException If the environment variable is undefined.
     */
    public function value()
    {
        if (null === $this->value) {
            $value = $this->isolator()->getenv($this->name());
            if (false === $value) {
                throw new Exception\UndefinedEnvironmentVariableException(
                    $this->name()
                );
            }

            $this->value = $value;
        }

        return $this->value;
    }

    /**
     * Get the value of this environment variable.
     *
     * @return string The environment variable value.
     */
    public function __toString()
    {
        try {
            $value = $this->value();
        } catch (Exception\UndefinedEnvironmentVariableException $e) {
            $value = '';
        }

        return $value;
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

    private $name;
    private $isolator;
    private $value;
}
