<?php


namespace Battis\IcsMunger\ConsolidateRecurrences;


use Battis\Calendar\Calendar;
use Battis\Calendar\Properties\Component\Descriptive\Summary;

class ConsolidateRecurrencesCalendar extends Calendar
{
    const DEFAULT_ORDER = [
    ];

    public function consolidate($property = Summary::class): void
    {
        $groups = [];
        foreach ($this->getAllEvents() as $event) {
            if (!empty($prop = $event->getProperty($property))) {
                $prop = (string)$prop->getValue();
                if (empty($groups[$prop])) {
                    $groups[$prop] = [$event];
                } else {
                    array_push($groups[$prop], $event);
                }
            }
        }
        foreach ($groups as $prop => $group) {

        }
    }
}
