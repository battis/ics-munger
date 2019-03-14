<?php


namespace Battis\IcsMunger\Tests\Calendar;


use Exception;
use PDO;

abstract class AbstractPersistentCalendarTestCase extends CalendarTest
{
    /**
     * @var PDO
     */
    private static $databaseHandle = null;

    /**
     * @var array
     */
    private $snapshots = [];

    /**
     * @param array $criteria
     * @param string $tableName
     * @param string $message
     * @throws Exception
     */
    public static function assertRowExists(array $criteria, string $tableName, string $message = ''): void
    {
        $clauses = [];
        foreach ($criteria as $key => $value) {
            array_push($clauses, "`$key` " . ($value === null ? 'IS' : '=') . " :$key");
        }
        $statement = self::getDatabase()->prepare("SELECT * FROM `$tableName` WHERE " . implode(' AND ', $clauses) . ' LIMIT 1');
        $statement->execute($criteria);
        self::assertEquals(1, $statement->rowCount(), $message);
    }

    /**
     * @param int $expected
     * @param string $query
     * @param array $params
     * @param string $message
     * @throws Exception
     */
    public static function assertQueryRowCount(int $expected, string $query, array $params = [], string $message = ''): void
    {
        $statement = self::getDatabase()->prepare($query);
        $statement->execute($params);
        self::assertEquals($expected, $statement->rowCount(), $message);
    }

    /**
     * @throws Exception
     */
    protected function pushDatabaseSnapshot(): void
    {
        $snapshot = [];
        foreach ($this->getDatabaseTables() as $tableName) {
            $data = $this->getDatabase()->query("SELECT * FROM `$tableName`");
            while ($row = $data->fetch()) {
                $snapshot[$tableName] = $row;
            }
        }
        array_push($this->snapshots, $snapshot);
    }

    /**
     * @return array
     * @throws Exception
     * FIXME I don't think this SELECT query actually works...
     */
    private function getDatabaseTables(): array
    {
        $tables = [];
        $statement = $this->getDatabase()->prepare('SELECT `table_name` FROM `information_schema.tables` WHERE `table_schema` = :dbname');
        $statement->execute(['dbname' => $GLOBALS['DB_DBNAME']]);
        while ($table = $statement->fetch()) {
            array_push($tables, $table['table_name']);
        }
        return $tables;
    }

    /**
     * @return PDO
     * @throws Exception
     */
    protected static function getDatabase(): PDO
    {
        if (self::$databaseHandle == null) {
            self::$databaseHandle = new PDO(
                "mysql:host={$GLOBALS['DB_HOST']};port={$GLOBALS['DB_PORT']};dbname={$GLOBALS['DB_DBNAME']}",
                $GLOBALS['DB_USER'],
                $GLOBALS['DB_PASSWORD']
            );
            self::loadSchema(
                self::$databaseHandle,
                realpath(__DIR__ . '/../../schema')
            );
        }
        return self::$databaseHandle;
    }

    /**
     * @param PDO $db
     * @param string $schemaPath
     * @throws Exception
     */
    private static function loadSchema(PDO $db, string $schemaPath): void
    {
        foreach (scandir($schemaPath) as $filename) {
            $filepath = realpath($schemaPath . "/$filename");
            if (preg_match('/.*\.sql/', $filepath) && is_file($filepath)) {
                if (!$db->query(file_get_contents($filepath))) {
                    throw new Exception("MySQL error " . $db->errorCode() . " on loading '$filepath'");
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function popDatabaseSnapshot(): void
    {
        if (empty($this->snapshots)) {
            throw new Exception('Tried to pop a database snapshot from an empty stack');
        } else {
            $snapshot = array_pop($this->snapshots);

            // drop any tables added since snapshot was taken
            foreach (array_diff($this->getDatabaseTables(), array_keys($snapshot)) as $newTable) {
                $this->getDatabase()->query("DROP TABLE IF EXISTS `$newTable`");
            }

            // restore table data from snapshot
            foreach ($snapshot as $tableName => $tableData) {
                $this->getDatabase()->query("TRUNCATE TABLE `$tableName`");
                foreach ($tableData as $row) {
                    $keys = array_keys($row);
                    $values = array_values($row);
                    $this->getDatabase()->query("INSERT INTO `$tableName` (`" . implode('`, `', $keys) . "`) VALUES ('" . implode("', '", $values) . "')");
                }
            }
        }
    }

    protected function loadFixture(): void
    {
    }

    protected function unloadFixture(): void
    {
    }
}
