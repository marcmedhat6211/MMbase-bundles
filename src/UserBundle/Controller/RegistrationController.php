<?php

namespace App\UserBundle\Controller;

use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\Form\RegistrationType;
use App\UserBundle\MMUserEvents;
use App\UserBundle\Security\CustomAuthenticator;
use App\UserBundle\Util\PasswordUpdaterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{

    /**
     * @Route("/register", name="app_registration")
     */
    public function register(
        Request                      $request,
        RequestStack                 $requestStack,
        PasswordUpdaterInterface     $passwordUpdater,
        EventDispatcherInterface     $eventDispatcher,
        EntityManagerInterface       $em,
        CustomAuthenticator          $authenticator,
        GuardAuthenticatorHandler    $guard
    ): Response
    {
        //if user is already logged in just redirect him to home and tell him that he needs to log out first
        if ($this->getUser()) {
            $this->addFlash('warning', 'You are already logged in as a user, please logout if you want to create another account with different credentials');
            return $this->redirectToRoute('fe_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->addRole("ROLE_USER");
            $formData = $form->getData();
            $user->setPlainPassword($formData->getPassword());
            $passwordUpdater->hashPassword($user);

            //dispatching an event that the registration is completed
            $event = new RegistrationEvent($user, $requestStack);
            $eventDispatcher->dispatch($event, MMUserEvents::REGISTRATION_COMPLETED);
            if ($user->registrationValidation["error"]) {
                $this->addFlash("danger", $user->registrationValidation["message"]);
                return $this->redirect($this->generateUrl('app_registration'));
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Signed Up successfully');

            //Login after Registration is complete
            return $guard->authenticateUserAndHandleSuccess($user, $request, $authenticator, 'main');
        }

        return $this->render('user/registration/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
