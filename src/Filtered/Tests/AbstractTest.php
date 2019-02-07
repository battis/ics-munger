<?php


namespace Battis\IcsMunger\Filtered\Tests;


use Battis\IcsMunger\Calendar\Event;
use Battis\IcsMunger\Filtered\AbstractFilter;


abstract class AbstractTest extends AbstractFilter
{
    abstract public function apply(Event $event): bool;
}
