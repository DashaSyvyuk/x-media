<?php

namespace App\Controller\Admin2;

use App\Entity\PaymentType;
use App\EventListener\ImageUploadSubscriber;
use App\Form\Admin2\PaymentTypeType;
use App\Repository\PaymentTypeRepository;
use App\Service\Admin2\EntityImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class SettingsPaymentTypeController extends AbstractController
{
    public function __construct(
        private readonly PaymentTypeRepository $paymentTypeRepository,
        private readonly EntityImageUploader $entityImageUploader,
        private readonly ImageUploadSubscriber $imageUploadSubscriber,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/settings/payment-types', name: 'admin2_settings_payment_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        $this->imageUploadSubscriber->ensureLocalDirFor(PaymentType::class);

        $paymentType = new PaymentType();
        $paymentType->setTitle('');
        $paymentType->setEnabled(true);
        $paymentType->setCost(0);

        return $this->handleSave($request, $paymentType, false);
    }

    #[Route('/admin/settings/payment-types/{id}', name: 'admin2_settings_payment_update', methods: ['POST'])]
    public function update(Request $request, int $id): Response
    {
        $this->imageUploadSubscriber->ensureLocalDirFor(PaymentType::class);

        $paymentType = $this->paymentTypeRepository->find($id);
        if (! $paymentType instanceof PaymentType) {
            throw $this->createNotFoundException('Спосіб оплати не знайдено.');
        }

        return $this->handleSave($request, $paymentType, true);
    }

    #[Route('/admin/settings/payment-types/{id}/delete', name: 'admin2_settings_payment_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_payment', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'payment']);
        }

        $paymentType = $this->paymentTypeRepository->find($id);
        if ($paymentType instanceof PaymentType) {
            $title = $paymentType->getTitle();
            $this->entityManager->remove($paymentType);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Спосіб оплати «%s» видалено.', $title));
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'payment']);
    }

    private function handleSave(Request $request, PaymentType $paymentType, bool $isEdit): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_payment', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'payment']);
        }

        $form = $this->createForm(PaymentTypeType::class, $paymentType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyUploadedIcon($form->get('iconFile')->getData(), $paymentType);

            if (! $isEdit) {
                $this->entityManager->persist($paymentType);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isEdit
                ? sprintf('Спосіб оплати «%s» збережено.', $paymentType->getTitle())
                : sprintf('Спосіб оплати «%s» створено.', $paymentType->getTitle()));
        } else {
            $this->addFlash('error', 'Перевірте правильність полів способу оплати.');
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'payment']);
    }

    private function applyUploadedIcon(mixed $file, PaymentType $paymentType): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $paymentType->setIcon($this->entityImageUploader->upload($file, PaymentType::class));
    }
}
