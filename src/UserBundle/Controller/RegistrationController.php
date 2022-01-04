<?php

namespace App\UserBundle\Controller;

use App\UserBundle\Entity\User;
use App\UserBundle\Form\UserType;
use App\UserBundle\Security\CustomAuthenticator;
use App\UserBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{
    private RequestStack $requestStack;
    private EntityManagerInterface $em;
    private CustomAuthenticator $authenticator;
    private GuardAuthenticatorHandler $guard;

    public function __construct(
        RequestStack              $requestStack,
        EntityManagerInterface    $em,
        CustomAuthenticator       $authenticator,
        GuardAuthenticatorHandler $guard
    )
    {
        $this->requestStack = $requestStack;
        $this->em = $em;
        $this->authenticator = $authenticator;
        $this->guard = $guard;
    }

    /**
     * @Route("/register", name="app_registration")
     */
    public function register(Request $request, UserService $userService): Response
    {
        //if user is already logged in just redirect him to home and tell him that he needs to log out first
        if ($this->getUser()) {
            $this->addFlash('warning', 'You are already logged in as a user, please logout if you want to create another account with different credentials');
            return $this->redirectToRoute('fe_home');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->remove("enabled");
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->setUserPassword($form, $user);

            if (!$userService->areCanonicalsValid($user)) {
                return $this->redirectToRoute("app_registration");
            }

            // persisting and adding the user to the database
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash("success", "Signed up successfully");
            return $this->guard->authenticateUserAndHandleSuccess($user, $this->requestStack->getCurrentRequest(), $this->authenticator, 'main');
        }

        return $this->render('user/registration/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
