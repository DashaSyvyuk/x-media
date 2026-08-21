<?php

namespace App\Controller\Admin2;

use App\Entity\User;
use App\Form\Admin2\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class UserEditController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/users/{id}/edit', name: 'admin2_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (! $user instanceof User) {
            throw $this->createNotFoundException('Користувача не знайдено.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Користувача #%d збережено.', $user->getId()));

            return $this->redirectToRoute('admin2_users');
        }

        return $this->render('admin2/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
