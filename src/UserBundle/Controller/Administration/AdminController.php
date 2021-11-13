<?php

namespace App\UserBundle\Controller\Administration;

use App\UserBundle\Entity\User;
use App\UserBundle\Event\RegistrationEvent;
use App\UserBundle\Form\AdministrationType;
use App\UserBundle\Form\RegistrationType;
use App\UserBundle\MMUserEvents;
use App\UserBundle\Repository\UserRepository;
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
        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * @Route("/administrators", name="admin_index", methods={"GET"})
     */
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $admins = $userRepository->findByRole("ROLE_ADMIN");
        return $this->render('admin/admin/index.html.twig', [
            "admins" => $admins,
        ]);
    }

    /**
     * @Route("/administrators/new", name="admin_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher, RequestStack $requestStack): Response
    {
        $user = new User();
        $form = $this->createForm(AdministrationType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $user->addRole("ROLE_ADMIN");
            $event = new RegistrationEvent($user, $requestStack);
            $eventDispatcher->dispatch($event, MMUserEvents::REGISTRATION_COMPLETED);
            if ($user->registrationValidation["error"]) {
                $this->addFlash("danger", $user->registrationValidation["message"]);
                return $this->redirect($this->generateUrl('app_registration'));
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash("success", "User created successfully");
            return $this->redirectToRoute("admin_index");
        }

        return $this->render('admin/admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}