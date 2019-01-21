<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use Battis\IcsMunger\Filtered\AbstractFilter;
use kigkonsult\iCalcreator\calendarComponent;

abstract class AbstractTransformation extends AbstractFilter
{
    /**
     * @param calendarComponent $component
     * @return calendarComponent
     */
    abstract public function transform(calendarComponent $component): calendarComponent;
}
