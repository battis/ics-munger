<?php


namespace Battis\IcsMunger\ConvertToInvitation;


use Battis\IcsMunger\Calendar\Calendar;

class CalendarWrapper extends Calendar
{
    public function removeContents(): void
    {
        while ($this->deleteComponent('vevent')) continue;
        while ($this->deleteComponent('vtodo')) continue;
        while ($this->deleteComponent('vjournal')) continue;
        while ($this->deleteComponent('vfreebusy')) continue;
    }
}
