<?php


namespace Battis\IcsMunger\Calendar;


use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;

class Calendar extends vcalendar
{
    const PRODUCT_IDENTIFIER = 'prodid';
    const VERSION = 'version';
    const CALENDAR_SCALE = 'calscale';
    const MIME_METHOD = 'method';
    const X_PROPERTY_NAME = 0;
    const X_PROPERTY_VALUE = 1;

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
            parent::__construct();
            $this->copy($data);
            return;
        } else {
            throw new CalendarException('Instantiation requires a Calendar object, configuration array, a URL, a filepath, or iCalendar text data, received ' . gettype($data) . ' instead');
        }
        parent::__construct($config);
        $this->parse($parseText);
    }

    /**
     * @param vcalendar $data
     */
    private function copy(vcalendar $data): void
    {
        // required properties
        $this->setProperty(self::PRODUCT_IDENTIFIER, $data->getProperty(self::PRODUCT_IDENTIFIER));
        $this->setProperty(self::VERSION, $data->getProperty(self::VERSION));

        // optional properties
        if ($calscale = $data->getProperty(self::CALENDAR_SCALE)) {
            $this->setProperty(self::CALENDAR_SCALE, $calscale);
        }
        if ($method = $data->getProperty(self::MIME_METHOD)) {
            $this->setProperty(self::MIME_METHOD, $method);
        }

        // X-properties and IANA-properties
        while ($xprop = $data->getProperty()) {
            $this->setProperty($xprop[self::X_PROPERTY_NAME], $xprop[self::X_PROPERTY_VALUE]);
        }

        // components
        while ($component = $data->getComponent()) {
            $this->addComponent($component);
        }
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
            if ($vevent) {
                return new Event($vevent);
            }
        }
        return false;
    }
}
