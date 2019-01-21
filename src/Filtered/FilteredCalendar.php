<?php


namespace Battis\IcsMunger\Filtered;


use Battis\IcsMunger\AbstractCalendar;
use Battis\IcsMunger\Filtered\Tests\AbstractTest;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\AndOp;
use Battis\IcsMunger\Filtered\Transformations\AbstractTransformation;
use Exception;
use kigkonsult\iCalcreator\vcalendar;

class FilteredCalendar extends AbstractCalendar
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
     * @param AbstractCalendar|vcalendar|array|string $data
     * @param AbstractTest|AbstractFilter[] $test
     * @param AbstractTransformation|AbstractTransformation[] $transformation
     * @throws Exception
     */
    public function __construct($data, $test = [], $transformation = [])
    {
        parent::__construct($data);
        $this->setTest($test);
        $this->setTransformations($transformation);

        $this->apply();
    }

    public function apply(): void
    {
        $this->applyTest();
        $this->applyTransformations();
    }

    public function applyTest(): void
    {
        /*
         * TODO Why on earth doesn't this catch everything on the first pass?
         */
        do {
            $deleted = 0;
            while ($event = $this->data->getComponent('vevent')) {
                $uid = $event->getProperty('uid');
                if ($this->test->apply($event) === false) {
                    $this->data->deleteComponent($uid);
                    $deleted++;
                }
            }
        } while ($deleted != 0);
    }

    public function applyTransformations(): void
    {
        if (!empty($this->transformations)) {
            while ($event = $this->data->getComponent('vevent')) {
                $uid = $event->getProperty('uid');
                $transformed = clone $event;
                foreach ($this->transformations as $transformation) {
                    $transformed = $transformation->transform($transformed);
                }
                if ($transformed != $event) { // intentional non-strict comparison
                    $this->data->setComponent($transformed, $uid);
                }
            }
        }
    }

    /**
     * @return AbstractTest
     */
    public function getTest(): AbstractTest
    {
        return $this->test;
    }

    /**
     * @param AbstractTest|AbstractTest[] $test
     * @throws Exception
     */
    public function setTest($test): void
    {
        if ($test instanceof AbstractTest) {
            $this->test = $test;
        } elseif (is_array($test)) {
            $this->test = new AndOp(...$test);
        } else {
            throw new Exception('Expected AbstractTest or AbstractTest[], received ' . gettype($test));
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
     * @throws Exception
     */
    public function setTransformations($transformation): void
    {
        if ($transformation instanceof AbstractTransformation) {
            $this->transformations = [$transformation];
        } elseif (is_array($transformation)) {
            $this->transformations = $transformation;
        } else {
            throw new Exception('Expected AbstractTransformation or AbstractTransformation[], received ' . gettype($transformation));
        }
    }
}
