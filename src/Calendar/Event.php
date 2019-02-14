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
     * @return DateTime|bool
     */
    public function getStart()
    {
        $d = $this->getProperty('dtstart');
        return DateTime::createFromFormat('Y-m-d H:i:s', "{$d['year']}-{$d['month']}-{$d['day']} {$d['hour']}:{$d['min']}:{$d['sec']}");
    }

    /**
     * @return string|bool
     */
    public function getUid()
    {
        return $this->getProperty('uid');
    }
}
