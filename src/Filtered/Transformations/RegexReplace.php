<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use kigkonsult\iCalcreator\calendarComponent;

class RegexReplace extends AbstractTransformation
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $replacement;

    public function __construct($properties, $pattern, $replacement)
    {
        parent::__construct($properties);
        $this->pattern = $pattern;
        $this->replacement = $replacement;
    }

    /**
     * @param calendarComponent $component
     * @return calendarComponent
     */
    public function transform(calendarComponent $component): calendarComponent
    {
        foreach ($this->properties as $property) {
            $component->setProperty($property, preg_replace($this->pattern, $this->replacement, $component->getProperty($property)));
        }
        return $component;
    }
}
