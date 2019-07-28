<?php


namespace Battis\IcsMunger\RetainHistory;


use Battis\Calendar\Component;

class ComponentDiff
{
    /** @var Component */
    private $old;

    /** @var Component */
    private $new;

    public function __construct(Component $old, Component $new)
    {
        $this->old = $old;
        $this->new = $new;
    }
}
