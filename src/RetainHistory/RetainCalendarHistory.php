<?php


namespace Battis\IcsMunger\RetainHistory;


use Battis\IcsMunger\Calendar\AbstractPersistentCalendar;
use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\IcsMungerException;
use DateTime;
use kigkonsult\iCalcreator\calendarComponent;
use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;
use PDO;

class RetainCalendarHistory extends AbstractPersistentCalendar
{
    /**
     * @var int
     */
    private $id = null;

    /**
     * @var string
     */
    private $name = null;

    /** @var int */
    private $sync = null;

    /**
     * CalendarWithHistory constructor.
     * @param Calendar|vcalendar|array|string $data
     * @param PDO $db
     * @param string $name
     * @throws IcsMungerException
     */
    public function __construct($data, PDO $db = null, $name = null)
    {
        if ($db == null && $data instanceof AbstractPersistentCalendar) {
            $db = $data->getDb();
        }
        parent::__construct($data, $db);
        if ($name === null) {
            if ($data instanceof Calendar && isset($data->name)) {
                $name = $data->name;
            } elseif (empty($name = $this->getConfig('url'))) {
                if (is_string($data) && empty($name = realpath($data))) {
                    throw new IcsMungerException("Cannot uniquely identify calendar name implicitly");
                }
            }
        }
        $this->setName($name);
        $this->sync();
    }

    /**
     * @throws CalendarException
     */
    public function sync(): void
    {
        $priorSyncTimestamp = $this->getSyncedTimestamp();
        $firstEventStart = $this->getFirstEventStart();
        $this->cacheLiveEvents();
        $this->recoverCachedEvents($priorSyncTimestamp, $firstEventStart);
    }

    public function getId(): int
    {
        if (empty($this->id)) {
            $statement = $this->prepare('INSERT INTO `calendars` SET `name` = :name ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `modified` = CURRENT_TIMESTAMP');
            $statement->execute(['name' => $this->name]);
            $this->id = (int)$this->getDb()->lastInsertId();
        }
        return $this->id;
    }

    /**
     * @return DateTime|boolean
     * @throws CalendarException
     */
    public function getFirstEventStart()
    {
        $start = false;
        $this->reset();
        while ($vevent = $this->getEvent()) {
            $dtstart = self::getStart($vevent);
            if ($start === null) $start = $dtstart;
            elseif ($dtstart < $start) $start = $dtstart;
        }
        return $start;
    }

    /**
     * @param calendarComponent $component
     * @return DateTime|false
     */
    private static function getStart(calendarComponent $component)
    {
        $dtstart = $component->getProperty('dtstart');
        return DateTime::createFromFormat('Y-m-d', "{$dtstart['year']}-{$dtstart['month']}-{$dtstart['day']}");
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @throws IcsMungerException
     */
    public function setName(string $name): void
    {
        if (strlen($name) > 0) {
            $this->name = $name;
        } else {
            throw new IcsMungerException('Name must be non-zero length string');
        }
    }

    /**
     * @return string|false
     */
    public function getSyncedTimestamp()
    {
        $statement = $this->prepare('
            SELECT `syncs`.`id` as `sync`, `events`.`modified` as `timestamp`
            FROM `events` LEFT JOIN `syncs`
              ON `events`.`sync` = `syncs`.`id`
            WHERE `syncs`.`calendar` = :calendar
            ORDER BY `events`.`modified` DESC
            LIMIT 1');
        $statement->execute(['calendar' => $this->getId()]);
        if ($row = $statement->fetch()) {
            return $row['timestamp'];
        }
        return false;
    }

    /**
     * @return int
     */
    protected function getSyncId(): int
    {
        if ($this->sync === null) {
            $statement = $this->prepare('INSERT INTO `syncs` SET `calendar` = :calendar');
            $statement->execute(['calendar' => $this->getId()]);
            $this->sync = (int)$this->getDb()->lastInsertId();
        }
        return $this->sync;
    }

    /**
     * @return void
     * @throws CalendarException
     */
    public function cacheLiveEvents(): void
    {
        $select = $this->prepare('SELECT * FROM `events` WHERE `calendar` = :calendar AND `uid` = :uid');
        $update = $this->prepare('UPDATE `events` SET `vevent` = :vevent, `sync` = :sync WHERE `id` = :id');
        $insert = $this->prepare('INSERT INTO `events` SET `calendar` = :calendar, `uid` = :uid, `vevent` = :vevent, `sync` = :sync');
        $this->reset();
        while ($e = $this->getEvent()) {
            $select->execute([
                'calendar' => $this->getId(),
                'uid' => $e->getUid()
            ]);
            if ($cache = $select->fetch()) {
                $update->execute([
                    'vevent' => $e->createComponent(),
                    'sync' => $this->getSyncId(),
                    'id' => $cache['id']
                ]);
            } else {
                $insert->execute([
                    'calendar' => $this->getId(),
                    'uid' => $e->getUid(),
                    'vevent' => $e->createComponent(),
                    'sync' => $this->getSyncId()
                ]);
            }
        }
    }

    /**
     * @param string|bool $priorSyncTimestamp
     * @param DateTime|bool $firstEventStart
     */
    public function recoverCachedEvents($priorSyncTimestamp = false, $firstEventStart = false): void
    {
        if ($priorSyncTimestamp !== false && $firstEventStart !== false) {
            $statement = $this->prepare('SELECT * FROM `events` WHERE `calendar` = :calendar AND `modified` <= :modified AND `sync` != :sync');
            $delete = $this->prepare('DELETE FROM `events` WHERE `id` = :id');
            $statement->execute([
                'calendar' => $this->getId(),
                'modified' => $priorSyncTimestamp,
                'sync' => $this->getSyncId()
            ]);
            while ($cache = $statement->fetch()) {
                $e = new vevent();
                $e->parse($cache['vevent']);
                if (self::getStart($e) < $firstEventStart) {
                    $this->addComponent($e);
                } else {
                    $delete->execute(['id' => $cache['id']]);
                }
            }
        }
        $this->sync = null;
    }
}
