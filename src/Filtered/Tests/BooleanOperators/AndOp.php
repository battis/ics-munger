<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use Battis\IcsMunger\Calendar\Event;

class AndOp extends AbstractOperator
{

    public function apply(Event $event): bool
    {
        $expression = true;
        foreach ($this->tests as $test) {
            $expression = $expression && $test->apply($event);
        }
        return $expression;
    }
}
