<?php


namespace Battis\IcsMunger\Calendar;


use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;

class Calendar extends vcalendar
{
    /**
     * Calendar constructor.
     * @param vcalendar|string|array $data
     * @throws CalendarException
     */
    public function __construct($data)
    {
        $config = [];
        $parseText = false;
        if (is_string($data)) {
            if (strpos($data, '://', 1) !== false) {
                $config['url'] = $data;
            } elseif (realpath($data)) {
                $parseText = file_get_contents($data);
            } else {
                $parseText = $data;
            }

        } elseif (is_array($data)) {
            $config = $data;
        } elseif ($data instanceof vcalendar) {
            foreach ($data as $property => $value) {
                $this->$property = $value;
            }
            return;
        } else {
            throw new CalendarException('Instantiation requires a Calendar object, configuration array, a URL, a filepath, or iCalendar text data, received ' . gettype($data) . ' instead');
        }
        parent::__construct($config);
        $this->parse($parseText);
    }

    public function reset(): void
    {
        unset($this->compix['INDEX']);
    }

    /**
     * @param string|null $uid
     * @return Event|bool
     * @throws CalendarException
     */
    public function getEvent($uid = null)
    {
        if ($uid !== null) {
            $component = $this->getComponent($uid);
            if ($component instanceof vevent) {
                return new Event($component);
            }
        } else {
            $vevent = $this->getComponent('vevent');
            if ($vevent) return new Event($vevent);
        }
        return false;
    }
}
