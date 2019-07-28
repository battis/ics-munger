<?php


namespace Battis\IcsMunger\Filtered;


use Battis\Calendar\Calendar;
use Battis\Calendar\Component;
use Battis\Calendar\Components\Event;
use Battis\Calendar\Property;

class FilteredCalendar extends Calendar
{
    /** @var callable */
    private $test = null;

    /** @var callable */
    private $transformation = null;

    /** @var bool */
    private $tested = false;

    /** @var bool */
    private $transformed = false;

    /**
     * FilteredCalendar constructor.
     * @param Property[] $properties
     * @param Component[] $components
     * @param callable $test function(Event): bool
     * @param callable $transformation function(Event): Event
     * @throws FilteredCalendarException
     */
    public function __construct(array $properties = [], array $components = [], callable $test = null, callable $transformation = null)
    {
        parent::__construct($properties, $components);
        $this->setTest($test);
        $this->setTransformation($transformation);
    }

    public function apply(): void
    {
        $this->applyTest();
        $this->applyTransformation();
    }

    public function applyTest(): void
    {
        if (!$this->tested) {
            $this->tested = true;
            if (!empty($test = $this->getTest())) {
                $trash = [];
                foreach ($this->getAllEvents() as $event) {
                    if (call_user_func($test, $event) === false) {
                        array_push($trash, $event);
                    }
                }
                foreach ($trash as $event) {
                    $this->removeComponent($event);
                }
            }
        }
    }

    public function applyTransformation(): void
    {
        if (!$this->transformed) {
            $this->transformed = true;
            if (!empty($transformation = $this->getTransformation())) {
                foreach ($this->getAllEvents() as $event) {
                    $transformed = clone $event;
                    $transformed = call_user_func($transformation, $transformed);
                    if ($event != $transformed) {
                        $this->setComponent($event, $transformed);
                    }
                }
            }
        }
    }

    /**
     * @return callable|null
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param callable $test function(Event): bool
     * @throws FilteredCalendarException
     */
    public function setTest(callable $test = null): void
    {
        if (!empty($test)) {
            $previousReportingLevel = error_reporting(E_ERROR | E_WARNING | E_PARSE);
            $result = call_user_func($test, new Event());
            error_reporting($previousReportingLevel);
            if (is_bool($result)) {
                $this->test = $test;
            } else {
                throw new FilteredCalendarException('Expected callable with signature `function(Event): bool`');
            }
        }
    }

    /**
     * @return callable|null
     */
    public function getTransformation()
    {
        return $this->transformation;
    }

    /**
     * @param callable|null $transformation function(Event): Event
     * @throws FilteredCalendarException
     */
    public function setTransformation(callable $transformation = null): void
    {
        if (!empty($transformation)) {
            $previousReportingLevel = error_reporting(E_ERROR | E_WARNING | E_PARSE);
            $result = call_user_func($transformation, new Event());
            error_reporting($previousReportingLevel);
            if ($result instanceof Event) {
                $this->transformation = $transformation;
            } else {
                throw new FilteredCalendarException('Expected callable with signature `function(Event): Event`');
            }
        }
    }
}
