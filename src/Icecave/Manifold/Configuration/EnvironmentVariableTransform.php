<?php
namespace Icecave\Manifold\Configuration;

use Eloquent\Schemer\Value\StringValue;
use Eloquent\Schemer\Value\Transform\AbstractValueTransform;
use Icecave\Isolator\Isolator;

/**
 * Replaces environment variable placeholders with actual values.
 */
class EnvironmentVariableTransform extends AbstractValueTransform
{
    /**
     * Construct a new environment variable transform.
     *
     * @param Isolator|null $isolator The isolator to use.
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * Transform a string value, replacing it with the value of an environment
     * variable if the string matches the pattern $VARIABLE_NAME.
     *
     * @param StringValue $value The string value to transform.
     *
     * @return StringValue                                     The transformed value.
     * @throws Exception\UndefinedEnvironmentVariableException If an undefined environment variable is requested.
     */
    public function visitStringValue(StringValue $value)
    {
        $rawValue = $value->value();
        if (preg_match('/^\$(\w+)$/', $rawValue, $matches)) {
            $replacement = $this->isolator()->getenv($matches[1]);
            if (false === $replacement) {
                throw new Exception\UndefinedEnvironmentVariableException(
                    $matches[1]
                );
            }

            $value = new StringValue($replacement);
        } elseif ('\\' === substr($rawValue, 0, 1)) {
            $value = new StringValue(substr($rawValue, 1));
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

    private $isolator;
}
