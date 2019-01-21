<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use Battis\IcsMunger\Filtered\Tests\AbstractTest;
use Exception;
use kigkonsult\iCalcreator\calendarComponent;

class NotOp extends AbstractOperator
{
    /**
     * Not constructor.
     * @param AbstractTest|AbstractTest[] $test
     * @throws Exception
     */
    public function __construct($test)
    {
        if ($test instanceof AbstractTest) {
            parent::__construct([$test]);
        } elseif (is_array($test)) {
            parent::__construct([$test[0]]);
        } else {
            throw new Exception('Expected Filter or Filter[], received ' . gettype($test));
        }
    }

    /**
     * @param calendarComponent $component
     * @return bool
     */
    public function apply(calendarComponent $component): bool
    {
        return !$this->tests[0]->apply($component);
    }
}
