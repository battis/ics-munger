<?php


namespace Battis\IcsMunger\ConvertToInvitation;


use Battis\IcsMunger\Calendar\AbstractPersistentCalendar;
use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use kigkonsult\iCalcreator\vcalendar;
use PDO;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;

class ConvertToInvitation extends AbstractPersistentCalendar
{

    /** @var Swift_Mailer */
    private $mailer;

    /** @var string */
    private $organizer;

    /** @var string */
    private $subject;

    /**
     * ConvertToInvitation constructor.
     * @param vcalendar|string|array $data
     * @param PDO $db
     * @param Swift_Mailer $mailer
     * @param string $organizer
     * @param string|string[] $attendee
     * @param string $subject
     * @throws CalendarException
     */
    public function __construct($data, PDO $db = null, Swift_Mailer $mailer, string $organizer, $attendee = null, string $subject = 'Calendar Update')
    {
        parent::__construct($data, $db);
        $this->setMailer($mailer);
        $this->setOrganizer($organizer);
        if ($attendee !== null) {
            $this->invite($attendee, $subject);
        }
    }

    /**
     * @param string $attendee
     * @param string $subject
     * @throws CalendarException
     */
    public function invite(string $attendee, string $subject = null): void
    {
        if ($subject === null) {
            $subject = $this->getSubject();
        }

        while ($event = $this->getEvent()) {
            $event->setMethod('REQUEST');
            $event->setOrganizer('calendar@battis.net');
            $event->setAttendee($attendee);
        }
        $this->setProperty(Calendar::METHOD, 'REQUEST');

        $wrapper = new CalendarWrapper($this);
        $wrapper->removeContents();

        $message = new Swift_Message();
        $message->setSubject($subject);
        $message->setTo($attendee);
        $message->setFrom($this->getOrganizer());

        while ($event = $this->getEvent()) {
            $wrapper->addComponent($event);
            $attachment = new Swift_Attachment();
            $attachment->setBody($wrapper->createCalendar());
            $attachment->setContentType('text/calendar; method=REQUEST');
            $attachment->setFilename('invitation.ics');
            // TODO quoted-printable encoding would make debugging easier
            $message->attach($attachment);
            $wrapper->removeContents();
        }

        $this->mailer->send($message);
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getOrganizer(): string
    {
        return $this->organizer;
    }

    public function setOrganizer(string $organizer): void
    {
        $this->organizer = $organizer;
    }

    protected function getMailer(): Swift_Mailer
    {
        return $this->mailer;
    }

    protected function setMailer(Swift_Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }
}
