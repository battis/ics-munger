<?php


namespace Battis\IcsMunger\Calendar;


use DateTime;
use kigkonsult\iCalcreator\vevent;

class Event extends vevent
{
    const SUMMARY = 'summary';
    const DESCRIPTION = 'description';
    const DTSTART = 'dtstart';
    const DTEND = 'dtend';
    const DURATION = 'duration';
    const LOCATION = 'location';
    const RDATE = 'rdate';
    const METHOD = 'method';

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
     * @return string|bool
     */
    public function getSummary()
    {
        return $this->getProperty(self::SUMMARY);
    }

    /**
     * @return string|bool
     */
    public function getDescription()
    {
        return $this->getProperty(self::DESCRIPTION);
    }

    /**
     * @return string|bool
     */
    public function getLocation()
    {
        return $this->getProperty(self::LOCATION);
    }

    /**
     * @param array $d
     * @return DateTime|bool
     */
    private function createDateTimeFromArray(array $d)
    {
        foreach (['hour', 'min', 'sec'] as $t) {
            if (!isset($d[$t])) {
                $d[$t] = 0;
            }
        }
        return DateTime::createFromFormat('Y-m-d H:i:s', "{$d['year']}-{$d['month']}-{$d['day']} {$d['hour']}:{$d['min']}:{$d['sec']}");
    }

    /**
     * @return DateTime|bool
     */
    public function getStart()
    {
        if (is_array($d = $this->getProperty(self::DTSTART))) {
            return $this->createDateTimeFromArray($d);
        }
        return $d;
    }

    /**
     * @return DateTime|bool
     */
    public function getEnd()
    {
        if (is_array($d = $this->getProperty(self::DTEND))) {
            return $this->createDateTimeFromArray($d);
        }
        return $d;
    }

    /**
     * @return string|bool
     */
    public function getDuration()
    {
        if ($result = $this->getProperty(self::DURATION)) {
            return $result;
        } elseif (($start = $this->getStart()) && ($end = $this->getEnd())) {
            $duration = $end->diff($start, true);
            return $duration->format('P%aDT%hH%iM%sS');
        }
    }

    /**
     * @return string|bool
     */
    public function getUid()
    {
        return $this->getProperty('uid');
    }

    public function setMethod(string $method)
    {
        return $this->setProperty(self::METHOD, $method);
    }

    /**
     * @return string|bool
     */
    public function getMethod()
    {
        return $this->getProperty(self::METHOD);
    }
}
