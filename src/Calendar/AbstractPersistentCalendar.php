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

    public function __construct($data, PDO $db)
    {
        parent::__construct($data);
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
     * @return PDOStatement|false
     */
    protected function prepare(string $statement)
    {
        return $this->db->prepare($statement);
    }

    /**
     * @param string $statement
     * @return PDOStatement|false
     */
    protected function query(string $statement)
    {
        return $this->db->query($statement);
    }
}
