<?php

namespace App\Controller\Admin2;

use App\Entity\Card;
use App\Form\Admin2\CardType;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_ADMIN')")]
final class SettingsCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardRepository $cardRepository,
    ) {
    }

    #[Route('/admin/settings/cards', name: 'admin2_settings_cards_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        return $this->handleSave($request, new Card(), false);
    }

    #[Route('/admin/settings/cards/{id}', name: 'admin2_settings_cards_update', methods: ['POST'])]
    public function update(Request $request, int $id): Response
    {
        $card = $this->cardRepository->find($id);
        if (! $card instanceof Card) {
            throw $this->createNotFoundException('Картку не знайдено.');
        }

        return $this->handleSave($request, $card, true);
    }

    #[Route('/admin/settings/cards/{id}/delete', name: 'admin2_settings_cards_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_card', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'cards']);
        }

        $card = $this->cardRepository->find($id);
        if ($card instanceof Card) {
            $title = $card->getTitle();
            $this->entityManager->remove($card);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Картку «%s» видалено.', $title));
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'cards']);
    }

    private function handleSave(Request $request, Card $card, bool $isEdit): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_card', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'cards']);
        }

        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! $isEdit) {
                $this->entityManager->persist($card);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isEdit
                ? sprintf('Картку «%s» збережено.', $card->getTitle())
                : sprintf('Картку «%s» створено.', $card->getTitle()));
        } else {
            $this->addFlash('error', 'Перевірте правильність полів картки.');
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'cards']);
    }
}
