<?php


namespace Battis\IcsMunger\Filtered;


use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Filtered\Tests\AbstractTest;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\AndOp;
use Battis\IcsMunger\Filtered\Transformations\AbstractTransformation;


class FilteredCalendar extends Calendar
{
    /**
     * @var AbstractTest;
     */
    private $test;

    /**
     * @var AbstractTransformation[]
     */
    private $transformations;

    /**
     * FilteredCalendar constructor.
     * @param $data
     * @param array $test
     * @param array $transformation
     * @throws FilteredCalendarException
     * @throws CalendarException
     */
    public function __construct($data, $test = [], $transformation = [])
    {
        parent::__construct($data);
        $this->setTest($test);
        $this->setTransformations($transformation);

        $this->apply();
    }

    /**
     * @throws CalendarException
     */
    public function apply(): void
    {
        $this->applyTest();
        $this->applyTransformations();
    }

    /**
     * @throws CalendarException
     */
    public function applyTest(): void
    {
        $trash = [];
        while ($event = $this->getEvent()) {
            if ($this->test->apply($event) === false) {
                array_push($trash, $event);
            }
        }
        foreach ($trash as $event) {
            $this->deleteComponent($event->getUid());
        }
    }

    /**
     * @throws CalendarException
     */
    public function applyTransformations(): void
    {
        while ($event = $this->getEvent()) {
            $transformed = clone $event;
            foreach ($this->getTransformations() as $transformation) {
                $transformed = $transformation->transform($transformed);
            }
            if ($event != $transformed) {
                $this->setComponent($transformed, $event->getUid());
            }
        }
    }

    public function getTest(): AbstractTest
    {
        return $this->test;
    }

    /**
     * @param AbstractTest|AbstractTest[] $test
     * @throws FilteredCalendarException
     */
    public function setTest($test): void
    {
        if ($test instanceof AbstractTest) {
            $this->test = $test;
        } elseif (is_array($test)) {
            $this->test = AndOp::expr($test);
        } else {
            throw new FilteredCalendarException('Expected AbstractTest or AbstractTest[], received ' . gettype($test));
        }
    }

    /**
     * @return AbstractTransformation[]
     */
    public function getTransformations(): array
    {
        return $this->transformations;
    }

    /**
     * @param AbstractTransformation|AbstractTransformation[] $transformation
     * @throws FilteredCalendarException
     */
    public function setTransformations($transformation): void
    {
        if ($transformation instanceof AbstractTransformation) {
            $this->transformations = [$transformation];
        } elseif (is_array($transformation)) {
            $this->transformations = $transformation;
        } else {
            throw new FilteredCalendarException('Expected AbstractTransformation or AbstractTransformation[], received ' . gettype($transformation));
        }
    }
}
