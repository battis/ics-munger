<?php


namespace Battis\IcsMunger\RetainHistory;


use Battis\Calendar\Component;

class CalendarDiff
{
    /** @var Component[] */
    private $added = [];

    /** @var Component[] */
    private $removed = [];

    /** @var ComponentDiff[] */
    private $changed = [];

    public function addAddition(Component $component): void
    {
        array_push($this->added, $component);
    }

    public function addRemoval(Component $component): void
    {
        array_push($this->removed, $component);
    }

    public function addChange(Component $old, Component $new): void
    {
        array_push($this->changed, new ComponentDiff($old, $new));
    }

    public function merge(CalendarDiff $other): void
    {
        $this->added = array_merge($this->added, $other->added);
        $this->removed = array_merge($this->removed, $other->removed);
        $this->changed = array_merge($this->changed, $other->changed);
    }
}
