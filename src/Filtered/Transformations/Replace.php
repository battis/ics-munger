<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use Battis\IcsMunger\Calendar\Event;

class Replace extends AbstractTransformation
{
    /**
     * @var string
     */
    private $search;

    /**
     * @var string
     */
    private $replace;

    /**
     * Replace constructor.
     * @param $properties
     * @param string $search
     * @param string $replace
     * @throws \Exception
     */
    public function __construct($properties, string $search, string $replace)
    {
        parent::__construct($properties);
        $this->search = $search;
        $this->replace = $replace;
    }

    public function transform(Event $event): Event
    {
        foreach ($this->properties as $property) {
            $event->setProperty($property, str_replace($this->search, $this->replace, $event->getProperty($property)));
        }
        return $event;
    }
}
