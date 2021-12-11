<?php

namespace App\UserBundle\EventListener;

use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\MMUserEvents;
use App\UserBundle\Model\MMUserInterface;
use App\UserBundle\Util\Canonicalizer;
use App\UserBundle\Util\CanonicalizerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Cache;

class RegistrationListener implements EventSubscriberInterface
{
    private CanonicalizerInterface $canonicalizer;
    private EntityManagerInterface $em;
    private ContainerInterface $container;

    public function __construct(CanonicalizerInterface $canonicalizer, EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->canonicalizer = $canonicalizer;
        $this->em = $em;
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            MMUserEvents::REGISTRATION_COMPLETED => 'onRegistrationComplete',
        ];
    }

    public function onRegistrationComplete(RegistrationEvent $event)
    {
        $user = $event->getUser();
        $user->registrationValidation = [
            "error" => false,
            "message" => ""
        ];
        $this->setUsernameCanonicalAndEmailCanonical($user, $this->canonicalizer);
        $areUsernameAndEmailValid = $this->validateUsernameAndEmail($user);
        if ($areUsernameAndEmailValid["error"]) {
            $user->registrationValidation = $areUsernameAndEmailValid;
            dump($user);
        }
    }

    //PRIVATE METHODS
    private function setUsernameCanonicalAndEmailCanonical(User $user, CanonicalizerInterface $canonicalizer): void
    {
        $user->setUsernameCanonical($canonicalizer->canonicalize($user->getUsername()));
        $user->setEmailCanonical($canonicalizer->canonicalize($user->getEmail()));
    }

    private function validateUsernameAndEmail(User $user): array
    {
        $returnValue = [
            "error" => false,
            "message" => ""
        ];

        $userByUsername = $this->em->getRepository(User::class)->findBy(['usernameCanonical' => $user->getUsernameCanonical()]);
        $userByEmail = $this->em->getRepository(User::class)->findBy(['emailCanonical' => $user->getEmailCanonical()]);

        if ($userByUsername && $user->getId() !== $userByUsername[0]->getId()) {
            $returnValue = [
                "error" => true,
                "message" => "Another user has the same username"
            ];
        }

        if ($userByEmail && $user->getId() !== $userByEmail[0]->getId()) {
            $returnValue = [
                "error" => true,
                "message" => "Another user has the same email"
            ];
        }

        if (($userByUsername && $userByEmail) && ($user->getId() !== $userByEmail[0]->getId())) {
            $returnValue = [
                "error" => true,
                "message" => "Another user has the same username and email"
            ];
        }

        return $returnValue;
    }
}