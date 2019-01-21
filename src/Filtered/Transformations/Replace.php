<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use kigkonsult\iCalcreator\calendarComponent;

class Replace extends AbstractTransformation
{
    /**
     * @var string
     */
    private $search;

    /**
     * @var string
     */
    private $replace;

    /**
     * Replace constructor.
     * @param $properties
     * @param string $search
     * @param string $replace
     * @throws \Exception
     */
    public function __construct($properties, string $search, string $replace)
    {
        parent::__construct($properties);
        $this->search = $search;
        $this->replace = $replace;
    }

    /**
     * @param calendarComponent $component
     * @return calendarComponent
     */
    public function transform(calendarComponent $component): calendarComponent
    {
        foreach ($this->properties as $property) {
            $component->setProperty($property, str_replace($this->search, $this->replace, $component->getProperty($property)));
        }
        return $component;
    }
}
