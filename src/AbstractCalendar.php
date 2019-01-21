<?php


namespace Battis\IcsMunger;


use Exception;
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
     * @throws Exception
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
     * @throws Exception
     * @link https://kigkonsult.se/iCalcreator/docs/using.html#vcalendar_constr kigconsult/icalcreator/vcalendar::__construct()
     */
    public function setData($data): void
    {
        $config = ['unique_id' => static::class];
        if ($data instanceof vcalendar) {
            $this->data = $data;
        } elseif (is_array($data)) {
            $config = $data + $config;
        } elseif (is_string($data)) {
            $config['url'] = $data;
        } else {
            throw new Exception('Array or string required, ' . gettype($data) . ' received');
        }
        $this->data = new vcalendar($config);
        if (empty($config['url']) && empty($config['filename'])) {
            $this->data->parse($data);
        } else {
            $this->data->parse();
        }
    }
}
