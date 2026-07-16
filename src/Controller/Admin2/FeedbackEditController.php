<?php

namespace App\Controller\Admin2;

use App\Entity\Feedback;
use App\Form\Admin2\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class FeedbackEditController extends AbstractController
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/feedbacks/{id}/edit', name: 'admin2_feedbacks_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $feedback = $this->feedbackRepository->find($id);
        if (! $feedback instanceof Feedback) {
            throw $this->createNotFoundException('Відгук не знайдено.');
        }

        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Відгук #%d збережено.', $feedback->getId()));

            return $this->redirectToRoute('admin2_feedbacks_edit', ['id' => $feedback->getId()]);
        }

        return $this->render('admin2/feedbacks/edit.html.twig', [
            'feedback' => $feedback,
            'form'     => $form,
        ]);
    }
}
