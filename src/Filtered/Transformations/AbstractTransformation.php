<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use Battis\IcsMunger\Calendar\Event;
use Battis\IcsMunger\Filtered\AbstractFilter;

abstract class AbstractTransformation extends AbstractFilter
{
    abstract public function transform(Event $event): Event;
}
