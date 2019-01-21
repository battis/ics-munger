<?php


namespace Battis\IcsMunger\Filtered\Tests\Comparisons;


use Battis\IcsMunger\Filtered\Tests\AbstractTest;
use Exception;
use kigkonsult\iCalcreator\calendarComponent;

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
     * @throws Exception
     */
    public function __construct($properties, $value)
    {
        parent::__construct($properties);
        $this->value = $value;
    }

    /**
     * @param calendarComponent $component
     * @return bool
     */
    public function apply(calendarComponent $component): bool
    {
        $expression = false;
        foreach ($this->properties as $property) {
            $expression = $expression || $this->comparison($component->getProperty($property));
        }
        return $expression;
    }

    /**
     * @param string $property
     * @return bool
     */
    protected abstract function comparison(string $property): bool;
}
