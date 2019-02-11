<?php


namespace Battis\IcsMunger\Calendar;


use DateTime;
use kigkonsult\iCalcreator\vevent;

class Event extends vevent
{
    /**
     * Event constructor.
     * @param vevent|array $data
     * @throws CalendarException
     */
    public function __construct($data = [])
    {
        if ($data instanceof vevent) {
            foreach ($data as $property => $value) {
                $this->$property = $value;
            }
        } elseif (is_array($data)) {
            parent::__construct($data);
        } else {
            throw new CalendarException('Require a configuration array or vevent object to instantiate, received ' . gettype($data));
        }
    }

    /**
     * @return DateTime|false
     */
    public function getStart()
    {
        return DateTime::createFromFormat('Y-m-d', "{$this->dtstart['year']}-{$this->dtstart['month']}-{$this->dtstart['day']}");
    }

    /**
     * @return string|false
     */
    public function getUid()
    {
        return $this->getProperty('uid');
    }
}
