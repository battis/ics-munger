<?php


namespace Battis\IcsMunger\ConvertToInvitation;


use Battis\IcsMunger\PersistentCalendar\Calendar;

class CalendarWrapper extends Calendar
{
    public function removeContents(): void
    {
        foreach (['vevent', 'vtodo', 'vjournal', 'vfreebusy'] as $componentType) {
            while ($this->deleteComponent($componentType)) {
                continue;
            }
        }
    }
}
