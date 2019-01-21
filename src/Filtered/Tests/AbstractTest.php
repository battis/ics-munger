<?php


namespace Battis\IcsMunger\Filtered\Tests;


use Battis\IcsMunger\Filtered\AbstractFilter;
use kigkonsult\iCalcreator\calendarComponent;

abstract class AbstractTest extends AbstractFilter
{
    /**
     * @param calendarComponent $component
     * @return bool
     */
    abstract public function apply(calendarComponent $component): bool;
}
