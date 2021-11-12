<?php

namespace App\UserBundle;

final class MMUserEvents
{
    /**
     * @Event("App\UserBundle\Event\RegistrationEvent")
     */
    const REGISTRATION_COMPLETED = 'mm_user.registration.completed';
}