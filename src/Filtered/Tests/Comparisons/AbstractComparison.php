<?php


namespace Battis\IcsMunger\Filtered\Tests\Comparisons;


use Battis\IcsMunger\Calendar\Event;
use Battis\IcsMunger\Filtered\FilterException;
use Battis\IcsMunger\Filtered\Tests\AbstractTest;

abstract class AbstractComparison extends AbstractTest
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * ComparisonFilter constructor.
     * @param string|string[] $properties
     * @param mixed $value
     * @throws FilterException
     */
    public function __construct($properties, $value)
    {
        parent::__construct($properties);
        $this->value = $value;
    }

    public function apply(Event $event): bool
    {
        $expression = false;
        foreach ($this->properties as $property) {
            $expression = $expression || $this->comparison($event->getProperty($property));
        }
        return $expression;
    }

    /**
     * @param string $property
     * @return bool
     */
    abstract protected function comparison(string $property): bool;
}
