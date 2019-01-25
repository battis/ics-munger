<?php


namespace Battis\IcsMunger;


use kigkonsult\iCalcreator\vcalendar;

abstract class AbstractCalendar
{
    /**
     * @var vcalendar
     */
    protected $data;

    /**
     * Calendar constructor.
     * @param AbstractCalendar|vcalendar|array|string $data
     * @throws IcsMungerException
     */
    public function __construct($data)
    {
        if ($data instanceof AbstractCalendar) {
            $this->data = clone $data->data;
        } else {
            $this->setData($data);
        }
    }

    /**
     * @return vcalendar
     */
    public function getData(): vcalendar
    {
        return $this->data;
    }

    /**
     * @param vcalendar|array|string $data URI or iCalendar text
     * @link https://kigkonsult.se/iCalcreator/docs/using.html#vcalendar_constr kigconsult/icalcreator/vcalendar::__construct()
     * @throws IcsMungerException
     */
    public function setData($data): void
    {
        $config = ['unique_id' => static::class];
        if ($data instanceof vcalendar) {
            $this->data = $data;
            return;
        } elseif (is_array($data)) {
            $config = $data + $config;
        } elseif (strstr($data, '://') == true) {
            $config['url'] = $data;
        } elseif (!is_string($data)) {
            throw new IcsMungerException('Array or string required, ' . gettype($data) . ' received');
        }
        $this->data = new vcalendar($config);
        if (empty($config['url']) && is_string($data)) {
            if (file_exists($data)) $data = file_get_contents($data);
            $this->data->parse($data);
        } else {
            $this->data->parse();
        }
    }
}
