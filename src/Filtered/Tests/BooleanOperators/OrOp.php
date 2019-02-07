<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use Battis\IcsMunger\Calendar\Event;

class OrOp extends AbstractOperator
{

    public function apply(Event $event): bool
    {
        $expression = false;
        foreach ($this->tests as $test) {
            $expression = $expression || $test->apply($event);
        }
        return $expression;
    }
}
