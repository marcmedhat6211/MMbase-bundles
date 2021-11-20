<?php

namespace App\UserBundle\Controller\Administration;

use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\Form\AdministrationType;
use App\UserBundle\Form\RegistrationType;
use App\UserBundle\MMUserEvents;
use App\UserBundle\Repository\UserRepository;
use App\UserBundle\Service\UserService;
use App\UserBundle\Util\PasswordUpdaterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="dashboard", methods={"GET"})
     */
    public function dashboard(): Response
    {
        if(!$this->getUser() || !$this->getUser()->hasRole("ROLE_ADMIN")) {
            $this->addFlash("danger", "You are not allowed to access this route");
            return $this->redirectToRoute("fe_home");
        }

        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * @Route("/administrator", name="admin_index", methods={"GET"})
     */
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $admins = $userRepository->findByRole("ROLE_ADMIN");
        return $this->render('admin/admin/index.html.twig', [
            "admins" => $admins,
        ]);
    }

    /**
     * @Route("/administrator/new", name="admin_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserService $userService): Response
    {
        $user = new User();
        $form = $this->createForm(AdministrationType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
//            $user->addRole("ROLE_ADMIN");
//            $formData = $form->getData();
//            $user->setPlainPassword($formData->getPassword());
//            $passwordUpdater->hashPassword($user);
//            $event = new RegistrationEvent($user, $requestStack);
//            $eventDispatcher->dispatch($event, MMUserEvents::REGISTRATION_COMPLETED);
//            if ($user->registrationValidation["error"]) {
//                $this->addFlash("danger", $user->registrationValidation["message"]);
//                return $this->redirect($this->generateUrl('app_registration'));
//            }
//
//            $em->persist($user);
//            $em->flush();
//
//            $this->addFlash("success", "User created successfully");
//            return $this->redirectToRoute("admin_index");
            return $userService->createUser($user, $form, "ROLE_ADMIN", "admin_new", "admin_index");
        }

        return $this->render('admin/admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}