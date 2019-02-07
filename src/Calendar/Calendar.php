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
    public function __construct($data = [])
    {
        if (is_string($data)) {
            if (strpos($data, '://', 1) !== false) {
                $data = ['url' => $data];
            }
        }
        if (is_array($data)) {
            parent::__construct($data);
            $this->parse();
        } elseif (is_string($data)) {
            if (realpath($data)) {
                $this->parse(file_get_contents($data));
            } else {
                $this->parse($data);
            }
        } elseif ($data instanceof vcalendar) {
            foreach ($data as $property => $value) {
                $this->$property = $value;
            }
            $this->parse();
        } else {
            throw new CalendarException('Instantiation requires a Calendar object, configuration array, a URL, a filepath, or iCalendar text data');
        }
    }

    public function reset(): void
    {
        $this->getComponent(0);
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
