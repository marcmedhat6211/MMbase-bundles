<?php

namespace App\UserBundle\Controller\Administration;

use App\UserBundle\Entity\User;
use App\UserBundle\Form\AdministrationType;
use App\UserBundle\Repository\UserRepository;
use App\UserBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{

    private RouterInterface $router;
    private EntityManagerInterface $em;

    public function __construct(
        RouterInterface          $router,
        EntityManagerInterface $em
    )
    {
        $this->router = $router;
        $this->em = $em;
    }

    /**
     * @Route("/", name="dashboard", methods={"GET"})
     */
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * @Route("/administrator", name="admin_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
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

        if ($form->isSubmitted() && $form->isValid()) {
            $user->addRole("ROLE_ADMIN");
            $userService->setUserPassword($form, $user);

            if (!$userService->areCanonicalsValid($user)) {
                return $this->redirectToRoute("admin_new");
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash("success", "Administrator created successfully");
            return $this->redirectToRoute("admin_index");
        }

        return $this->render('admin/admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/administrator/{id}/edit", name="admin_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UserService $userService, User $user): Response
    {
        $form = $this->createForm(AdministrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->setUserPassword($form, $user);

            if (!$userService->areCanonicalsValid($user)) {
                return $this->redirectToRoute("admin_edit", ["id" => $user->getId()]);
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash("success", "Administrator updated successfully");
            return $this->redirectToRoute("admin_index");
        }

        return $this->render('admin/admin/edit.html.twig', [
            'form' => $form->createView(),
            'admin' => $user
        ]);
    }
}