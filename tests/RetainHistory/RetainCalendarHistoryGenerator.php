<?php


namespace Battis\IcsMunger\Tests\RetainHistory;


use Battis\IcsMunger\PersistentCalendar\Event;
use Battis\IcsMunger\Tests\data\CalendarGenerator;
use DateInterval;
use DateTime;
use Exception;

class RetainCalendarHistoryGenerator extends CalendarGenerator
{
    /**
     * @param int $count
     * @param float $overlapPercentage
     * @return CalendarGenerator[][]
     * @throws Exception
     */
    public function snapshots(int $count, float $overlapPercentage = 0.9): array
    {
        $starts = array_keys($this->getProperty(Event::DTSTART));
        sort($starts);
        $start = new DateTime($starts[0]);

        $ends = array_keys($this->getProperty(Event::DTEND));
        sort($ends);
        $end = new DateTime(array_pop($ends));

        $result = [];
        $window = (int)(($end->getTimestamp() - $start->getTimestamp()) / ($overlapPercentage + (($count - 1) * (1 - $overlapPercentage))));
        $step = (int)((1 - $overlapPercentage) * $window);
        $snapshotStart = $start;
        for ($i = 0; $i < $count; $i++) {
            $snapshot = new CalendarGenerator(null, null, 0);
            $snapshotEnd = clone $snapshotStart;
            $snapshotEnd->add($this->interval($window));
            unset($this->compix);
            foreach ($this->selectComponents($snapshotStart, $snapshotEnd, null, null, null, null, 'vevent', true, true, false) as $e) {
                $snapshot->addComponent($e);
            }
            array_push($result, $snapshot);
            $snapshotStart->add($this->interval($step));
        }
        return $result;
    }

    /**
     * @param int $seconds
     * @return DateInterval
     * @throws Exception
     */
    private function interval(int $seconds): DateInterval
    {
        $years = (int)($seconds / (60 * 60 * 24 * 365));
        if ($years) $seconds = $seconds % ($years * 60 * 60 * 24 * 365);
        $days = (int)($seconds / (60 * 60 * 24));
        if ($days) $seconds = $seconds % ($days * 60 * 60 * 24);
        $hours = (int)($seconds / (60 * 60));
        if ($hours) $seconds = $seconds % ($hours * 60 * 60);
        $minutes = (int)($seconds / 60);
        if ($minutes) $seconds = $seconds % ($minutes * 60);
        return new DateInterval("P{$years}Y{$days}DT{$hours}H{$minutes}M{$seconds}S");
    }
}
