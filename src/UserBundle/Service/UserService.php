<?php

namespace App\UserBundle\Service;

use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\MMUserEvents;
use App\UserBundle\Model\MMUserInterface;
use App\UserBundle\Util\PasswordUpdaterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class UserService
{
    private PasswordUpdaterInterface $passwordUpdater;
    private RequestStack $requestStack;
    private EventDispatcherInterface $eventDispatcher;
    private FlashBagInterface $flashBag;

    public function __construct(
        PasswordUpdaterInterface $passwordUpdater,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        FlashBagInterface $flashBag
    )
    {
        $this->passwordUpdater = $passwordUpdater;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->flashBag = $flashBag;
    }

    /**
     * This method sets the user's passwords
     * @param FormInterface $form
     * @param User $user
     */
    public function setUserPassword(FormInterface $form, User $user): void
    {
        $formData = $form->getData();
        $user->setPlainPassword($formData->getPassword());
        $this->passwordUpdater->hashPassword($user);
    }

    /**
     * This method checks if the user's canonicals are valid
     * @param MMUserInterface $user
     * @return bool
     */
    public function areCanonicalsValid(MMUserInterface $user): bool
    {
        $event = new RegistrationEvent($user, $this->requestStack);
        $this->eventDispatcher->dispatch($event, MMUserEvents::REGISTRATION_COMPLETED);
        // handling the canonical fields errors
        if ($user->registrationValidation["error"]) {
            $this->flashBag->add("danger", $user->registrationValidation["message"]);
            return false;
        }

        return true;
    }
}