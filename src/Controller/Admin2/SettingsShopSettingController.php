<?php

namespace App\Controller\Admin2;

use App\Entity\Setting;
use App\Form\Admin2\ShopSettingType;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class SettingsShopSettingController extends AbstractController
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/settings/shop-settings', name: 'admin2_settings_shop_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        $setting = new Setting();
        $setting->setTitle('');
        $setting->setSlug('');
        $setting->setValue('');

        return $this->handleSave($request, $setting, false);
    }

    #[Route('/admin2/settings/shop-settings/{id}', name: 'admin2_settings_shop_update', methods: ['POST'])]
    public function update(Request $request, int $id): Response
    {
        $setting = $this->settingRepository->find($id);
        if (! $setting instanceof Setting) {
            throw $this->createNotFoundException('Налаштування не знайдено.');
        }

        return $this->handleSave($request, $setting, true);
    }

    #[Route('/admin2/settings/shop-settings/{id}/delete', name: 'admin2_settings_shop_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_shop', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'shop']);
        }

        $setting = $this->settingRepository->find($id);
        if ($setting instanceof Setting) {
            $title = $setting->getTitle();
            $this->entityManager->remove($setting);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Параметр «%s» видалено.', $title));
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'shop']);
    }

    private function handleSave(Request $request, Setting $setting, bool $isEdit): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_shop', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'shop']);
        }

        $form = $this->createForm(ShopSettingType::class, $setting, ['is_edit' => $isEdit]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! $isEdit) {
                $this->entityManager->persist($setting);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isEdit
                ? sprintf('Параметр «%s» збережено.', $setting->getTitle())
                : sprintf('Параметр «%s» створено.', $setting->getTitle()));
        } else {
            $this->addFlash('error', 'Перевірте правильність полів параметра.');
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'shop']);
    }
}
