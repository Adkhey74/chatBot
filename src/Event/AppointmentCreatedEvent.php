<?php

namespace App\Event;

use App\Entity\Appointment;

final class AppointmentCreatedEvent
{
    public function __construct(private readonly Appointment $appointment) {}

    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }
}