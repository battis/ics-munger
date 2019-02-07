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
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $containsCachedData = false;

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
        if (!$this->containsCachedData) {
            $priorSync = $this->getMostRecentSyncTimestamp();
            $this->cacheData();
            $this->restoreCachedData($priorSync);
        }
    }

    /**
     * @return string|null
     */
    public function getMostRecentSyncTimestamp()
    {
        $statement = $this->prepare('SELECT * FROM `calendars` ORDER BY `modified` DESC LIMIT 1');
        $statement->execute();
        if ($row = $statement->fetch()) {
            return $row['modified'];
        } else {
            return null;
        }
    }

    /**
     * @throws CalendarException
     */
    private function cacheData(): void
    {
        $select = $this->prepare('SELECT * FROM `events` WHERE `calendar` = :calendar AND `uid` = :uid');
        $update = $this->prepare('UPDATE `events` SET `vevent` = :vevent WHERE `id` = :id');
        $insert = $this->prepare('INSERT INTO `events` SET `calendar` = :calendar, `uid` = :uid, `vevent` = :vevent');

        if (!$this->containsCachedData) {
            $this->reset();
            while ($vevent = $this->getEvent()) {
                $uid = $vevent->getProperty('uid');
                $text = $vevent->createComponent();
                $select->execute([
                    'calendar' => $this->getId(),
                    'uid' => $uid
                ]);
                if ($row = $select->fetch()) {
                    $update->execute([
                        'id' => $row['id'],
                        'vevent' => $text
                    ]);
                } else {
                    $insert->execute([
                        'calendar' => $this->getId(),
                        'uid' => $uid,
                        'vevent' => $text
                    ]);
                }
            }
        }
    }

    public function getId(): int
    {
        if (empty($this->id)) {
            $statement = $this->prepare('INSERT INTO `calendars` SET `name` = :name ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `modified` = CURRENT_TIMESTAMP');
            $statement->execute(['name' => $this->name]);
            $this->id = $this->getDb()->lastInsertId();
        }
        return $this->id;
    }

    /**
     * @param string|null $priorSyncTimestamp
     * @throws CalendarException
     */
    private function restoreCachedData($priorSyncTimestamp): void
    {
        if (!$this->containsCachedData && $priorSyncTimestamp != null) {

            $cachedData = $this->prepare('SELECT * FROM `events` WHERE `calendar` = :calendar AND `MODIFIED` <= :modified');
            $deleteCachedData = $this->prepare('DELETE FROM `events` WHERE `id` = :id');

            $start = $this->getFirstEventStart();

            $cachedData->execute([':calendar' => $this->getId(), ':modified' => $priorSyncTimestamp]);
            while ($row = $cachedData->fetch()) {
                $vevent = new vevent();
                $vevent->parse($row['vevent']);
                if (self::getStart($vevent) < $start) {
                    if ($this->getComponent($row['uid'])) {
                        $deleteCachedData->execute(['id' => $row['id']]);
                    } else {
                        $this->addComponent($vevent);
                    }
                } else {
                    $deleteCachedData->execute(['id' => $row['id']]);
                }
            }
            $this->containsCachedData = true;
        }
    }

    /**
     * @return DateTime
     * @throws CalendarException
     */
    public function getFirstEventStart(): DateTime
    {
        $start = null;
        $this->reset();
        while ($vevent = $this->getEvent()) {
            $dtstart = self::getStart($vevent);
            if ($start === null) $start = $dtstart;
            elseif ($dtstart < $start) $start = $dtstart;
        }
        return $start;
    }

    private static function getStart(calendarComponent $component): DateTime
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
}
