<?php

namespace App\Controller\Admin2;

use App\Entity\Circulation;
use App\Entity\CirculationPayment;
use App\Form\Admin2\CirculationPaymentEntryType;
use App\Form\Admin2\CirculationType;
use App\Repository\CirculationPaymentRepository;
use App\Repository\CirculationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class CirculationShowController extends AbstractController
{
    public function __construct(
        private readonly CirculationRepository $circulationRepository,
        private readonly CirculationPaymentRepository $circulationPaymentRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/circulations/{id}', name: 'admin2_circulations_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        return $this->renderShow($this->findCirculation($id));
    }

    #[Route('/admin2/circulations/{id}/settings', name: 'admin2_circulations_settings', methods: ['POST'])]
    public function updateSettings(Request $request, int $id): Response
    {
        $circulation = $this->findCirculation($id);
        $form        = $this->createForm(CirculationType::class, $circulation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Налаштування каси збережено.');
        } else {
            $this->addFlash('error', 'Не вдалося зберегти налаштування.');
        }

        return $this->redirectToRoute('admin2_circulations_show', ['id' => $circulation->getId()]);
    }

    #[Route('/admin2/circulations/{id}/payments', name: 'admin2_circulations_payments_create', methods: ['POST'])]
    public function createPayment(Request $request, int $id): Response
    {
        $circulation = $this->findCirculation($id);
        $payment     = new CirculationPayment();
        $payment->setCirculation($circulation);
        $payment->setSum(0);
        $payment->setNote('');

        $form = $this->createForm(CirculationPaymentEntryType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $circulation->addPayment($payment);
            $this->entityManager->persist($payment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Операцію додано.');
        } else {
            $this->addFlash('error', 'Не вдалося додати операцію.');
        }

        return $this->redirectToRoute('admin2_circulations_show', ['id' => $circulation->getId()]);
    }

    #[Route(
        '/admin2/circulations/{id}/payments/{paymentId}/edit',
        name: 'admin2_circulations_payments_edit',
        methods: ['POST'],
    )]
    public function editPayment(Request $request, int $id, int $paymentId): Response
    {
        $circulation = $this->findCirculation($id);
        $payment     = $this->findPayment($circulation, $paymentId);

        $form = $this->createForm(CirculationPaymentEntryType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Операцію оновлено.');
        } else {
            $this->addFlash('error', 'Не вдалося оновити операцію.');
        }

        return $this->redirectToRoute('admin2_circulations_show', ['id' => $circulation->getId()]);
    }

    #[Route(
        '/admin2/circulations/{id}/payments/{paymentId}/delete',
        name: 'admin2_circulations_payments_delete',
        methods: ['POST'],
    )]
    public function deletePayment(Request $request, int $id, int $paymentId): Response
    {
        if (! $this->isCsrfTokenValid('admin2_circulation_payment_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_circulations_show', ['id' => $id]);
        }

        $circulation = $this->findCirculation($id);
        $payment     = $this->findPayment($circulation, $paymentId);

        $circulation->removePayment($payment);
        $this->entityManager->remove($payment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Операцію видалено.');

        return $this->redirectToRoute('admin2_circulations_show', ['id' => $circulation->getId()]);
    }

    private function renderShow(Circulation $circulation): Response
    {
        $payment = new CirculationPayment();
        $payment->setCirculation($circulation);
        $payment->setSum(0);

        return $this->render('admin2/circulations/show.html.twig', [
            'circulation'  => $circulation,
            'balance'      => (int) $circulation->getTotal(),
            'payments'     => $this->circulationPaymentRepository->findByCirculationOrdered($circulation),
            'settingsForm' => $this->createForm(CirculationType::class, $circulation)->createView(),
            'paymentForm'  => $this->createForm(CirculationPaymentEntryType::class, $payment)->createView(),
        ]);
    }

    private function findCirculation(int $id): Circulation
    {
        $circulation = $this->circulationRepository->find($id);
        if (! $circulation instanceof Circulation) {
            throw $this->createNotFoundException('Касу не знайдено.');
        }

        return $circulation;
    }

    private function findPayment(Circulation $circulation, int $paymentId): CirculationPayment
    {
        $payment = $this->circulationPaymentRepository->find($paymentId);
        if (! $payment instanceof CirculationPayment || $payment->getCirculation()->getId() !== $circulation->getId()) {
            throw $this->createNotFoundException('Операцію не знайдено.');
        }

        return $payment;
    }
}
