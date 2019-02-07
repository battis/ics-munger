<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use Battis\IcsMunger\Calendar\Event;
use Battis\IcsMunger\Filtered\FilterException;
use Battis\IcsMunger\Filtered\Tests\AbstractTest;


class NotOp extends AbstractOperator
{
    /**
     * Not constructor.
     * @param AbstractTest|AbstractTest[] $test
     * @throws FilterException
     */
    public function __construct($test)
    {
        if ($test instanceof AbstractTest) {
            parent::__construct([$test]);
        } elseif (is_array($test)) {
            parent::__construct([$test[0]]);
        } else {
            throw new OperatorException('Expected Filter or Filter[], received ' . gettype($test));
        }
    }

    /**
     * @param Event $event
     * @return bool
     */
    public function apply(Event $event): bool
    {
        return !$this->tests[0]->apply($event);
    }
}
