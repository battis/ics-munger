<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use kigkonsult\iCalcreator\calendarComponent;
use Michelf\Markdown;

class RenderMarkdown extends AbstractTransformation
{
    /**
     * RenderMarkdown constructor.
     * @param string[] $properties (Optional, defaults to `['description']`)
     * @throws \Exception
     */
    public function __construct($properties = ['description'])
    {
        parent::__construct($properties);
    }

    /**
     * @param calendarComponent $component
     * @return calendarComponent
     */
    public function transform(calendarComponent $component): calendarComponent
    {
        foreach ($this->properties as $property) {
            $component->setProperty($property, Markdown::defaultTransform(str_replace('\n', "\n\n", $component->getProperty($property))));
        }
        return $component;
    }
}
