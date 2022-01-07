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
 * @Route("/user")
 */
class UserController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;
    }

    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(
        UserRepository $userRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        //@TODO: fix this and get users by role
        $search = new \stdClass();
        $search->deleted = 0;
        $allUsers = $userRepository->filter($search);
        $users = [];
        foreach ($allUsers as $user) {
            $userRoles = $user->getRoles();
            if (in_array(User::ROLE_DEFAULT, $userRoles) && !in_array(User::ROLE_ADMIN, $userRoles)) {
                $users[] = $user;
            }
        }

        $pagination = $paginator->paginate($users, $request->query->getInt('page', 1), 10);

        return $this->render('admin/user/index.html.twig', [
            "pagination" => $pagination,
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserService $userService): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->setUserPassword($form, $user);

            if (!$userService->areCanonicalsValid($user)) {
                return $this->redirectToRoute("user_new");
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash("success", "User created successfully");
            return $this->redirectToRoute("user_index");
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UserService $userService, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->setUserPassword($form, $user);

            if (!$userService->areCanonicalsValid($user)) {
                return $this->redirectToRoute("user_edit", ["id" => $user->getId()]);
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash("success", "User updated successfully");
            return $this->redirectToRoute("user_index");
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/{id}/delete", name="user_delete", methods={"GET", "POST"})
     */
    public function delete(User $user): Response
    {
        $user->setDeleted(new \DateTime());
        $user->setDeletedBy($this->getUser()->getUsername());
        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash("success", "User Deleted Successfully");
        return $this->redirectToRoute("user_index");
    }
}