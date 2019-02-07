<?php


namespace Battis\IcsMunger\Filtered\Transformations;


use Battis\IcsMunger\Calendar\Event;

class RegexReplace extends AbstractTransformation
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $replacement;

    public function __construct($properties, string $pattern, string $replacement)
    {
        parent::__construct($properties);
        $this->pattern = $pattern;
        $this->replacement = $replacement;
    }

    public function transform(Event $event): Event
    {
        foreach ($this->properties as $property) {
            $event->setProperty($property, preg_replace($this->pattern, $this->replacement, $event->getProperty($property)));
        }
        return $event;
    }
}
