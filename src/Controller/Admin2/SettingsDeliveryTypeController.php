<?php

namespace App\Controller\Admin2;

use App\Entity\DeliveryType;
use App\EventListener\ImageUploadSubscriber;
use App\Form\Admin2\DeliveryTypeType;
use App\Repository\DeliveryTypeRepository;
use App\Service\Admin2\EntityImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class SettingsDeliveryTypeController extends AbstractController
{
    public function __construct(
        private readonly DeliveryTypeRepository $deliveryTypeRepository,
        private readonly EntityImageUploader $entityImageUploader,
        private readonly ImageUploadSubscriber $imageUploadSubscriber,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/settings/delivery-types/new', name: 'admin2_settings_delivery_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->imageUploadSubscriber->ensureLocalDirFor(DeliveryType::class);

        $deliveryType = new DeliveryType();
        $deliveryType->setTitle('');
        $deliveryType->setEnabled(true);
        $deliveryType->setNeedAddressField(true);
        $deliveryType->setIsNovaPoshta(false);
        $deliveryType->setCost(0);
        $deliveryType->setPriority(0);

        return $this->handleForm($request, $deliveryType, true);
    }

    #[Route(
        '/admin/settings/delivery-types/{id}/edit',
        name: 'admin2_settings_delivery_edit',
        methods: ['GET', 'POST'],
    )]
    public function edit(Request $request, int $id): Response
    {
        $this->imageUploadSubscriber->ensureLocalDirFor(DeliveryType::class);

        $deliveryType = $this->deliveryTypeRepository->find($id);
        if (! $deliveryType instanceof DeliveryType) {
            throw $this->createNotFoundException('Спосіб доставки не знайдено.');
        }

        return $this->handleForm($request, $deliveryType, false);
    }

    #[Route('/admin/settings/delivery-types/{id}/delete', name: 'admin2_settings_delivery_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_delivery', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'delivery']);
        }

        $deliveryType = $this->deliveryTypeRepository->find($id);
        if ($deliveryType instanceof DeliveryType) {
            $title = $deliveryType->getTitle();
            $this->entityManager->remove($deliveryType);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Спосіб доставки «%s» видалено.', $title));
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'delivery']);
    }

    private function handleForm(Request $request, DeliveryType $deliveryType, bool $isNew): Response
    {
        $form = $this->createForm(DeliveryTypeType::class, $deliveryType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyUploadedIcon($form->get('iconFile')->getData(), $deliveryType);

            if ($isNew) {
                $this->entityManager->persist($deliveryType);
            }

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $isNew
                    ? sprintf('Спосіб доставки «%s» створено.', $deliveryType->getTitle())
                    : sprintf('Спосіб доставки «%s» збережено.', $deliveryType->getTitle()),
            );

            return $this->redirectToRoute('admin2_settings_delivery_edit', ['id' => $deliveryType->getId()]);
        }

        return $this->render('admin2/settings/delivery_edit.html.twig', [
            'deliveryType' => $deliveryType,
            'form'         => $form,
            'isNew'        => $isNew,
        ]);
    }

    private function applyUploadedIcon(mixed $file, DeliveryType $deliveryType): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $deliveryType->setIcon($this->entityImageUploader->upload($file, DeliveryType::class));
    }
}
