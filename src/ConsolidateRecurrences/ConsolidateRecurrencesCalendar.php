<?php


namespace Battis\IcsMunger\ConsolidateRecurrences;


use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Calendar\Event;

class ConsolidateRecurrencesCalendar extends Calendar
{
    public function __construct($data, bool $compareSummary = true, bool $compareDescription = true, bool $compareLocation = true, bool $compareDuration = true)
    {
        parent::__construct($data);
        $this->consolidate($compareSummary, $compareDescription, $compareLocation, $compareDuration);
    }

    /**
     * @param bool $compareSummary
     * @param bool $compareDescription
     * @param bool $compareLocation
     * @param bool $compareDuration
     * @throws CalendarException
     */
    public function consolidate(bool $compareSummary = true, bool $compareDescription = true, bool $compareLocation = true, bool $compareDuration = true): void
    {
        /** @var ComparableEvent[] $events */
        $events = [];
        while ($event = $this->getEvent()) {
            array_push($events, $event);
        }

        while (!empty($events)) {
            $event = array_pop($events);
            $start = $event->getStart();
            $dtstart = $event->getProperty(Event::DTSTART);
            if (!($rdates = $event->getProperty(Event::RDATE))) {
                $rdates = [];
            }
            /*
             * FIXME RDATE recurrences aren't honored by Outlook, and appear to be confuse Google Calendar
             * https://support.microsoft.com/en-us/help/2643084/outlook-receives-a-message-that-has-an-attachment-that-is-named-not-su
             * Outlook is, at least, more honest and just claims not to understand events with RDATE recurrences
             *
             * https://developers.google.com/calendar/recurringevents
             * Google seems to think that RDATE recurrences are about creating all-day events of some sort
             */
            foreach ($events as $i => $e) {
                if ($event->equivalent($e, $compareSummary, $compareDescription, $compareLocation, $compareDuration, false)) {
                    if ($start < $e->getStart()) {
                        array_push($rdates, $e->getProperty(Event::DTSTART));
                    } else {
                        array_push($rdates, $dtstart);
                        $start = $e->getStart();
                        $dtstart = $e->getProperty(Event::DTSTART);
                    }
                    $this->deleteComponent($e->getUid());
                    unset($events[$i]);
                }
            }
            if (!empty($rdates)) {
                $event->setProperty(Event::DTSTART, $dtstart);
                $event->setProperty(Event::RDATE, $rdates);
                $this->deleteComponent($event->getUid());
                // TODO there must be a good strategy for generating a UID
                $event->setUid(
                    (implode('-', [
                        ($compareSummary ? $event->getSummary() : ''),
                        ($compareDescription ? $event->getDescription() : ''),
                        ($compareLocation ? $event->getLocation() : ''),
                        ($compareDuration ? $event->getDuration() : '')
                    ])) . '-consolidated'
                );
                $this->addComponent($event);
            }
        }
    }

    /**
     * @param string|null $uid
     * @return ComparableEvent|bool
     * @throws CalendarException
     */
    public function getEvent($uid = null)
    {
        if ($result = parent::getEvent($uid)) {
            $result = new ComparableEvent($result);
        }
        return $result;
    }
}
