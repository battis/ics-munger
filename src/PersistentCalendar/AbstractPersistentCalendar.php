<?php


namespace Battis\IcsMunger\PersistentCalendar;


use Battis\Calendar\Calendar;
use Battis\Calendar\Component;
use Battis\Calendar\Property;
use PDO;
use PDOStatement;


abstract class AbstractPersistentCalendar extends Calendar
{
    /**
     * @var PDO
     */
    private $db = null;

    /**
     * AbstractPersistentCalendar constructor.
     * @param Property[] $properties
     * @param Component[] $components
     * @param PDO $db
     */
    public function __construct(array $properties = [], array $components = [], PDO $db)
    {
        parent::__construct($properties, $components);
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
