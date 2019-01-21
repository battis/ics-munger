<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use kigkonsult\iCalcreator\calendarComponent;

class OrOp extends AbstractOperator
{

    /**
     * @param calendarComponent $component
     * @return bool
     */
    public function apply(calendarComponent $component): bool
    {
        $expression = false;
        foreach ($this->tests as $test) {
            $expression = $expression || $test->apply($component);
        }
        return $expression;
    }
}