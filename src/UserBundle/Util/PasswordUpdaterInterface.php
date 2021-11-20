<?php

namespace App\UserBundle\Util;

use App\UserBundle\Model\MMUserInterface;

/**
 * @author Marc Medhat <marcmedhat6211@gmail.com>
 */
interface PasswordUpdaterInterface
{
    public function hashPassword(MMUserInterface $user);
}