<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use Battis\IcsMunger\Calendar\Event;
use Michelf\Markdown;

class RenderMarkdown extends AbstractTransformation
{
    /**
     * RenderMarkdown constructor.
     * @param string[] $properties (Optional, defaults to `['description']`)
     * @throws \Exception
     */
    public function __construct($properties = ['description'])
    {
        parent::__construct($properties);
    }

    public function transform(Event $event): Event
    {
        foreach ($this->properties as $property) {
            $event->setProperty($property, Markdown::defaultTransform($event->getProperty($property)));
        }
        return $event;
    }
}
