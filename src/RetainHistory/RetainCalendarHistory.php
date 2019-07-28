<?php


namespace Battis\IcsMunger\RetainHistory;


use Battis\Calendar\Component;
use Battis\Calendar\Exceptions\ValueException;
use Battis\Calendar\Properties\Component\DateTime\DateTimeStart;
use Battis\Calendar\Properties\Component\Relationship\UniqueIdentifier;
use Battis\Calendar\Property;
use Battis\Calendar\Workflows\iCalendar;
use Battis\IcsMunger\PersistentCalendar\AbstractPersistentCalendar;
use DateTime;
use Exception;
use PDO;

class RetainCalendarHistory extends AbstractPersistentCalendar
{
    const INIFINITE_FUTURE = 'ZZZ';

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
     * @param Property[] $properties
     * @param Component[] $components
     * @param PDO $db
     * @param string $name
     * @throws RetainCalendarHistoryException
     */
    public function __construct(array $properties = [], array $components = [], PDO $db, string $name)
    {
        parent::__construct($properties, $components, $db);
        $this->setName($name);
    }

    /**
     * @throws Exception
     *
     * TODO Allow specification of comparison criteria beyond or instead of UID
     */
    public function sync(): CalendarDiff
    {
        $result = new CalendarDiff();
        $priorSyncTimestamp = $this->getSyncedTimestamp();
        if ($firstEventStart = $this->getFirstEventStart()) {
            $result->merge($this->cacheLiveEvents());
            $result->merge($this->recoverCachedEvents($priorSyncTimestamp, $firstEventStart));
        }
        return $result;
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
     * @return DateTime
     * @throws Exception
     */
    public function getFirstEventStart(): ?string
    {
        $first = self::INIFINITE_FUTURE;
        foreach ($this->getAllEvents() as $event) {
            if (($current = (string)$event->getProperty(DateTimeStart::class)->getValue()) < $first) {
                $first = $current;
            }
        }
        if ($first !== self::INIFINITE_FUTURE) {
            return $first;
        }
        return null;
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
     * @throws RetainCalendarHistoryException
     */
    public function setName(string $name): void
    {
        if (strlen($name) > 0) {
            $this->name = $name;
        } else {
            throw new RetainCalendarHistoryException('Name must be non-zero length string');
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
     * @return CalendarDiff
     * @throws ValueException
     */
    public function cacheLiveEvents(): CalendarDiff
    {
        $result = new CalendarDiff();
        $select = $this->prepare('SELECT * FROM `events` WHERE `calendar` = :calendar AND `uid` = :uid');
        $update = $this->prepare('UPDATE `events` SET `vevent` = :vevent, `sync` = :sync WHERE `id` = :id');
        $insert = $this->prepare('INSERT INTO `events` SET `calendar` = :calendar, `uid` = :uid, `vevent` = :vevent, `sync` = :sync');
        foreach ($this->getAllEvents() as $e) {
            $uid = (string)$e->getProperty(UniqueIdentifier::class)->getValue();
            $vevent = iCalendar::export($e);
            $select->execute([
                'calendar' => $this->getId(),
                'uid' => $uid
            ]);
            if ($cache = $select->fetch()) {
                if ($cache['vevent'] != $vevent) {
                    $update->execute([
                        'vevent' => $vevent,
                        'sync' => $this->getSyncId(),
                        'id' => $cache['id']
                    ]);
                    $cachedEvent = iCalendar::parse($cache['vevent']);
                    $result->addChange($cachedEvent, $e);
                }
            } else {
                $insert->execute([
                    'calendar' => $this->getId(),
                    'uid' => $uid,
                    'vevent' => $vevent,
                    'sync' => $this->getSyncId()
                ]);
                $result->addAddition($e);
            }
        }
        return $result;
    }

    /**
     * @param string|bool $priorSyncTimestamp
     * @param string $firstEventStart (Optional, default `INFINITE_FUTURE`)
     * @return CalendarDiff
     * @throws ValueException
     */
    public function recoverCachedEvents($priorSyncTimestamp = false, $firstEventStart = self::INIFINITE_FUTURE): CalendarDiff
    {
        $result = new CalendarDiff();
        if ($priorSyncTimestamp !== false && $firstEventStart !== false) {
            $statement = $this->prepare('SELECT * FROM `events` WHERE `calendar` = :calendar AND `modified` <= :modified AND `sync` != :sync');
            $delete = $this->prepare('DELETE FROM `events` WHERE `id` = :id');
            $statement->execute([
                'calendar' => $this->getId(),
                'modified' => $priorSyncTimestamp,
                'sync' => $this->getSyncId()
            ]);
            while ($cache = $statement->fetch()) {
                $e = iCalendar::parse($cache['vevent']);
                // FIXME
                if ((string)$e->getProperty(DateTimeStart::class)->getValue() < $firstEventStart) {
                    $this->addComponent($e);
                } else {
                    $delete->execute(['id' => $cache['id']]);
                    $result->addRemoval($e);
                }
            }
        }
        $this->sync = null;
        return $result;
    }
}
