<?php


namespace Battis\IcsMunger;


use PDO;

/**
 * Class AbstractDbCalendar
 * @package Battis\IcsMunger
 */
abstract class AbstractDbCalendar extends AbstractCalendar
{
    /**
     * @var PDO
     */
    protected $db;

    /**
     * AbstractDbCalendar constructor.
     * @param $data
     * @param PDO $db
     * @throws IcsMungerException
     */
    public function __construct($data, PDO $db)
    {
        parent::__construct($data);
        $this->db = $db;
    }
}
