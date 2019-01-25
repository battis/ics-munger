<?php


namespace Battis\IcsMunger\Filtered;


use Battis\IcsMunger\IcsMungerException;

class AbstractFilter
{
    /**
     * @var string[]
     */
    protected $properties;

    /**
     * Filter constructor.
     * @param string|string[] $properties
     * @throws IcsMungerException
     */
    public function __construct($properties)
    {
        if (is_string($properties)) {
            $this->properties = [$properties];
        } elseif (is_array($properties)) {
            foreach ($properties as $property) {
                if (!is_string($property)) {
                    throw new IcsMungerException('Expected string[] included ' . gettype($property));
                }
            }
            $this->properties = $properties;
        } else {
            throw new IcsMungerException('Expected string or string[], received ' . gettype($properties));
        }
    }

    /**
     * @param mixed ...$params
     * @return AbstractFilter
     * @throws IcsMungerException
     */
    public static function expr(...$params): AbstractFilter
    {
        return new static(...$params);
    }
}
