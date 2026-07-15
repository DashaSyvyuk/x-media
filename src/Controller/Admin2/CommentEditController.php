<?php

namespace App\Controller\Admin2;

use App\Entity\Comment;
use App\Form\Admin2\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class CommentEditController extends AbstractController
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/comments/{id}/edit', name: 'admin2_comments_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $comment = $this->commentRepository->find($id);
        if (! $comment instanceof Comment) {
            throw $this->createNotFoundException('Коментар не знайдено.');
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Коментар #%d збережено.', $comment->getId()));

            return $this->redirectToRoute('admin2_comments_edit', ['id' => $comment->getId()]);
        }

        return $this->render('admin2/comments/edit.html.twig', [
            'comment' => $comment,
            'form'    => $form,
        ]);
    }
}
