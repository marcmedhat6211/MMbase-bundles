<?php

namespace App\UserBundle\Service;

use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\MMUserEvents;
use App\UserBundle\Util\PasswordUpdaterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;

class UserService
{
    private PasswordUpdaterInterface $passwordUpdater;
    private EventDispatcherInterface $eventDispatcher;
    private RequestStack $requestStack;
    private RouterInterface $router;
    private EntityManagerInterface $em;
    private  FlashBagInterface $flashBag;

    public function __construct(
        PasswordUpdaterInterface $passwordUpdater,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        RouterInterface $router,
        EntityManagerInterface $em,
        FlashBagInterface $flashBag
    )
    {
        $this->passwordUpdater = $passwordUpdater;
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->em = $em;
        $this->flashBag = $flashBag;
    }

    public function createUser(User $user, FormInterface $form, string $userRole, string $errorRouteName, string $successRouteName): Response
    {
        $formData = $form->getData();

        $user->addRole($userRole);

        // hashing and setting user password
        $user->setPlainPassword($formData->getPassword());
        $this->passwordUpdater->hashPassword($user);

        // firing the registration complete event
        $event = new RegistrationEvent($user, $this->requestStack);
        $this->eventDispatcher->dispatch($event, MMUserEvents::REGISTRATION_COMPLETED);

        // handling the canonical fields errors
        if ($user->registrationValidation["error"]) {
            $this->flashBag->add("danger", $user->registrationValidation["message"]);
            $url = $this->router->generate($errorRouteName);
            return new RedirectResponse($url);
        }

        // persisting and adding the user to the database
        $this->em->persist($user);
        $this->em->flush();

        $this->flashBag->add("success", "User Created Successfully");
        $url = $this->router->generate($successRouteName);
        return new RedirectResponse($url);
    }
}