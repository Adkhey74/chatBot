<?php

use App\Entity\Appointment;

final class AppointementCreatedEvent
{
    public function __construct(private readonly Appointment $appointment) {}

    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }
}
