<?php


namespace Battis\IcsMunger\Calendar;


use PDO;
use PDOStatement;


abstract class AbstractPersistentCalendar extends Calendar
{
    /**
     * @var PDO
     */
    private $db;

    public function __construct($data, PDO $db = null)
    {
        parent::__construct($data);
        if ($db = null && $data instanceof AbstractPersistentCalendar) {
            if (($db = $data->getDb()) === null) {
                throw new PersistentCalendarException('PDO instance not provided and cannot be inferred');
            }
        }
        $this->setDb($db);
    }

    protected function getDb(): PDO
    {
        return $this->db;
    }

    protected function setDb(PDO $db): void
    {
        $this->db = $db;
    }

    /**
     * @param string $statement
     * @return PDOStatement|boolean
     */
    protected function prepare(string $statement)
    {
        return $this->db->prepare($statement);
    }
}
