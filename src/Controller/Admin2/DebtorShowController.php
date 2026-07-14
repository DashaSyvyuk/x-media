<?php

namespace App\Controller\Admin2;

use App\Entity\Debtor;
use App\Entity\DebtorPayment;
use App\Form\Admin2\DebtorPaymentEntryType;
use App\Form\Admin2\DebtorType;
use App\Repository\DebtorPaymentRepository;
use App\Repository\DebtorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class DebtorShowController extends AbstractController
{
    public function __construct(
        private readonly DebtorRepository $debtorRepository,
        private readonly DebtorPaymentRepository $debtorPaymentRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/debtors/{id}', name: 'admin2_debtors_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $debtor = $this->findDebtor($id);

        return $this->renderShow($debtor);
    }

    #[Route('/admin2/debtors/{id}/settings', name: 'admin2_debtors_settings', methods: ['POST'])]
    public function updateSettings(Request $request, int $id): Response
    {
        $debtor = $this->findDebtor($id);
        $form   = $this->createForm(DebtorType::class, $debtor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Налаштування контакту збережено.');
        } else {
            $this->addFlash('error', 'Не вдалося зберегти налаштування.');
        }

        return $this->redirectToRoute('admin2_debtors_show', ['id' => $debtor->getId()]);
    }

    #[Route('/admin2/debtors/{id}/payments', name: 'admin2_debtors_payments_create', methods: ['POST'])]
    public function createPayment(Request $request, int $id): Response
    {
        $debtor  = $this->findDebtor($id);
        $payment = new DebtorPayment();
        $payment->setDebtor($debtor);
        $payment->setSum(0);
        $payment->setNote('');

        $form = $this->createForm(DebtorPaymentEntryType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $debtor->addPayment($payment);
            $this->entityManager->persist($payment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Операцію додано.');
        } else {
            $this->addFlash('error', 'Не вдалося додати операцію.');
        }

        return $this->redirectToRoute('admin2_debtors_show', ['id' => $debtor->getId()]);
    }

    #[Route('/admin2/debtors/{id}/payments/{paymentId}/edit', name: 'admin2_debtors_payments_edit', methods: ['POST'])]
    public function editPayment(Request $request, int $id, int $paymentId): Response
    {
        $debtor  = $this->findDebtor($id);
        $payment = $this->findPayment($debtor, $paymentId);

        $form = $this->createForm(DebtorPaymentEntryType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Операцію оновлено.');
        } else {
            $this->addFlash('error', 'Не вдалося оновити операцію.');
        }

        return $this->redirectToRoute('admin2_debtors_show', ['id' => $debtor->getId()]);
    }

    #[Route('/admin2/debtors/{id}/payments/{paymentId}/delete', name: 'admin2_debtors_payments_delete', methods: ['POST'])]
    public function deletePayment(Request $request, int $id, int $paymentId): Response
    {
        if (! $this->isCsrfTokenValid('admin2_debtor_payment_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_debtors_show', ['id' => $id]);
        }

        $debtor  = $this->findDebtor($id);
        $payment = $this->findPayment($debtor, $paymentId);

        $debtor->removePayment($payment);
        $this->entityManager->remove($payment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Операцію видалено.');

        return $this->redirectToRoute('admin2_debtors_show', ['id' => $debtor->getId()]);
    }

    private function renderShow(Debtor $debtor): Response
    {
        $payment = new DebtorPayment();
        $payment->setDebtor($debtor);
        $payment->setSum(0);

        return $this->render('admin2/debtors/show.html.twig', [
            'debtor'       => $debtor,
            'balance'      => (int) $debtor->getTotal(),
            'payments'     => $this->debtorPaymentRepository->findByDebtorOrdered($debtor),
            'settingsForm' => $this->createForm(DebtorType::class, $debtor)->createView(),
            'paymentForm'  => $this->createForm(DebtorPaymentEntryType::class, $payment)->createView(),
        ]);
    }

    private function findDebtor(int $id): Debtor
    {
        $debtor = $this->debtorRepository->find($id);
        if (! $debtor instanceof Debtor) {
            throw $this->createNotFoundException('Контакт не знайдено.');
        }

        return $debtor;
    }

    private function findPayment(Debtor $debtor, int $paymentId): DebtorPayment
    {
        $payment = $this->debtorPaymentRepository->find($paymentId);
        if (! $payment instanceof DebtorPayment || $payment->getDebtor()->getId() !== $debtor->getId()) {
            throw $this->createNotFoundException('Операцію не знайдено.');
        }

        return $payment;
    }
}
