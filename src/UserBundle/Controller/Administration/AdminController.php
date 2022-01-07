<?php

namespace App\UserBundle\Controller\Administration;

use App\UserBundle\Entity\User;
use App\UserBundle\Form\UserType;
use App\UserBundle\Repository\UserRepository;
use App\UserBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administrator")
 */
class AdminController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;
    }

    /**
     * @Route("/", name="admin_index", methods={"GET"})
     */
    public function index(
        UserRepository $userRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        $admins = $userRepository->findByRole(User::ROLE_ADMIN);
        $pagination = $paginator->paginate($admins, $request->query->getInt('page', 1), 10);

        return $this->render('admin/admin/index.html.twig', [
            "pagination" => $pagination
        ]);
    }

    /**
     * @Route("/new", name="admin_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserService $userService): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->addRole(User::ROLE_ADMIN);
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
     * @Route("/{id}/edit", name="admin_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UserService $userService, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
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

    /**
     * @Route("/{id}/delete", name="admin_delete", methods={"GET", "POST"})
     */
    public function delete(User $user): Response
    {
        $user->setDeleted(new \DateTime());
        $user->setDeletedBy($this->getUser()->getUsername());
        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash("success", "Admin Deleted Successfully");
        return $this->redirectToRoute("admin_index");
    }
}