<?php


namespace Battis\IcsMunger\ConsolidateRecurrences;


use Battis\IcsMunger\Calendar\Event;

class ComparableEvent extends Event
{
    public function equivalent(Event $other, bool $compareSummary = true, bool $compareDescription = true, bool $compareLocation = true, bool $compareDuration = true, $compareDateTime = true): bool
    {
        return (
            ($compareSummary ? $this->getSummary() == $other->getSummary() : true) &&
            ($compareDescription ? $this->getDescription() == $other->getDescription() : true) &&
            ($compareLocation ? $this->getLocation() == $other->getLocation() : true) &&
            ($compareDuration ? $this->getDuration() == $other->getDuration() : true) &&
            ($compareDateTime ? $this->getStart() == $other->getStart() : true)
        );
    }
}
