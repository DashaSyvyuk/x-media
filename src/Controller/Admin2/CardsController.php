<?php

namespace App\Controller\Admin2;

use App\Entity\Card;
use App\Entity\CardOperation;
use App\Repository\CardOperationRepository;
use App\Repository\CardRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_ADMIN')")]
final class CardsController extends AbstractController
{
    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly CardOperationRepository $operationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/cards', name: 'admin2_cards', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin2/cards/index.html.twig', [
            'cards' => $this->cardRepository->findBy([], ['id' => 'ASC']),
        ]);
    }

    #[Route('/admin/cards/all-operations', name: 'admin2_cards_all_operations', methods: ['GET'])]
    public function allOperations(): Response
    {
        return $this->render('admin2/cards/all_operations.html.twig', [
            'operations' => $this->operationRepository->findAllOrderedDesc(),
        ]);
    }

    #[Route('/admin/cards/{id}', name: 'admin2_cards_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $card = $this->cardRepository->find($id);
        if (! $card instanceof Card) {
            throw $this->createNotFoundException('Картку не знайдено.');
        }

        return $this->render('admin2/cards/show.html.twig', [
            'card'   => $card,
            'months' => $this->operationRepository->findGroupedByMonth($card),
        ]);
    }

    // ---------- Operations CRUD ----------

    #[Route('/admin/cards/{id}/operations', name: 'admin2_cards_operations_store', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function storeOperation(Request $request, int $id): Response
    {
        $card = $this->cardRepository->find($id);
        if (! $card instanceof Card) {
            throw $this->createNotFoundException();
        }

        if (! $this->isCsrfTokenValid('admin2_card_op', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_cards');
        }

        $amount = (string) $request->request->get('amount', '0');
        $note   = (string) $request->request->get('note', '');

        $op = new CardOperation();
        $op->setCard($card);
        $op->setAmount($amount);
        $op->setNote($note ?: null);
        $op->setOperatedAt(new DateTime());

        $this->entityManager->persist($op);
        $this->entityManager->flush();

        $this->addFlash('success', 'Операцію додано.');

        return $this->redirectToRoute('admin2_cards_show', ['id' => $id]);
    }

    #[Route('/admin/cards/operations/{opId}/edit', name: 'admin2_cards_operations_update', methods: ['POST'], requirements: ['opId' => '\d+'])]
    public function updateOperation(Request $request, int $opId): Response
    {
        $op = $this->operationRepository->find($opId);
        if (! $op instanceof CardOperation) {
            throw $this->createNotFoundException();
        }

        if (! $this->isCsrfTokenValid('admin2_card_op', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_cards_show', ['id' => $op->getCard()->getId()]);
        }

        $op->setAmount((string) $request->request->get('amount', $op->getAmount()));
        $op->setNote((string) $request->request->get('note', '') ?: null);

        $dateStr = (string) $request->request->get('operated_at', '');
        if ($dateStr !== '') {
            $parsed = DateTime::createFromFormat('Y-m-d', $dateStr);
            if ($parsed instanceof DateTime) {
                $op->setOperatedAt($parsed);
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Операцію збережено.');

        return $this->redirectToRoute('admin2_cards_show', ['id' => $op->getCard()->getId()]);
    }

    #[Route('/admin/cards/operations/{opId}/done', name: 'admin2_cards_operations_done', methods: ['POST'], requirements: ['opId' => '\d+'])]
    public function doneOperation(Request $request, int $opId): Response
    {
        $op = $this->operationRepository->find($opId);
        if (! $op instanceof CardOperation) {
            throw $this->createNotFoundException();
        }

        if (! $this->isCsrfTokenValid('admin2_card_op', (string) $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false], 400);
        }

        $op->setIsDone(! $op->isDone());
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true, 'done' => $op->isDone()]);
    }

    #[Route('/admin/cards/operations/{opId}/delete', name: 'admin2_cards_operations_delete', methods: ['POST'], requirements: ['opId' => '\d+'])]
    public function deleteOperation(Request $request, int $opId): Response
    {
        $op = $this->operationRepository->find($opId);
        if (! $op instanceof CardOperation) {
            throw $this->createNotFoundException();
        }

        if (! $this->isCsrfTokenValid('admin2_card_op', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_cards_show', ['id' => $op->getCard()->getId()]);
        }

        $cardId = $op->getCard()->getId();
        $this->entityManager->remove($op);
        $this->entityManager->flush();
        $this->addFlash('success', 'Операцію видалено.');

        return $this->redirectToRoute('admin2_cards_show', ['id' => $cardId]);
    }
}
