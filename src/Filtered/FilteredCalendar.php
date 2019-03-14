<?php


namespace Battis\IcsMunger\Filtered;


use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Calendar\Event;


class FilteredCalendar extends Calendar
{
    /** @var callable */
    private $test = null;

    /** @var callable */
    private $transformation = null;

    /**
     * FilteredCalendar constructor.
     * @param $data
     * @param callable $test function(Event): bool
     * @param callable $transformation function(Event): Event
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function __construct($data, callable $test = null, callable $transformation = null)
    {
        parent::__construct($data);
        $this->setTest($test);
        $this->setTransformation($transformation);
        $this->apply();
    }

    /**
     * @throws CalendarException
     */
    public function apply(): void
    {
        $this->applyTest();
        $this->applyTransformation();
    }

    /**
     * @throws CalendarException
     */
    public function applyTest(): void
    {
        if (!empty($test = $this->getTest())) {
            $trash = [];
            while ($event = $this->getEvent()) {
                if (call_user_func($test, $event) === false) {
                    array_push($trash, $event);
                }
            }
            foreach ($trash as $event) {
                $this->deleteComponent($event->getUid());
            }
        }
    }

    /**
     * @throws CalendarException
     */
    public function applyTransformation(): void
    {
        if (!empty($transformation = $this->getTransformation())) {
            while ($event = $this->getEvent()) {
                $transformed = clone $event;
                $transformed = call_user_func($transformation, $transformed);
                if ($event != $transformed) {
                    $this->setComponent($transformed, $event->getUid());
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
     * @throws CalendarException
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
     * @throws CalendarException
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
