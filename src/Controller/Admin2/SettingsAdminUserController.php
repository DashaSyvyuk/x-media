<?php

namespace App\Controller\Admin2;

use App\Entity\AdminUser;
use App\Form\Admin2\AdminUserSettingsType;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class SettingsAdminUserController extends AbstractController
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/settings/admins', name: 'admin2_settings_admins_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        $adminUser = new AdminUser();
        $adminUser->setEmail('');
        $adminUser->setName('');
        $adminUser->setSurname('');
        $adminUser->setPhone('');
        $adminUser->setPassword('');
        $adminUser->setRoles([AdminUser::ROLE_USER]);

        return $this->handleSave($request, $adminUser, false);
    }

    #[Route('/admin2/settings/admins/{id}', name: 'admin2_settings_admins_update', methods: ['POST'])]
    public function update(Request $request, int $id): Response
    {
        $adminUser = $this->adminUserRepository->find($id);
        if (! $adminUser instanceof AdminUser) {
            throw $this->createNotFoundException('Адміна не знайдено.');
        }

        return $this->handleSave($request, $adminUser, true);
    }

    #[Route('/admin2/settings/admins/{id}/delete', name: 'admin2_settings_admins_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_admin', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'admins']);
        }

        /** @var AdminUser $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->getId() === $id) {
            $this->addFlash('error', 'Не можна видалити власний акаунт.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'admins']);
        }

        $adminUser = $this->adminUserRepository->find($id);
        if ($adminUser instanceof AdminUser) {
            $email = $adminUser->getEmail();
            $this->entityManager->remove($adminUser);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Адміна «%s» видалено.', $email));
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'admins']);
    }

    private function handleSave(Request $request, AdminUser $adminUser, bool $isEdit): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_admin', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'admins']);
        }

        $form = $this->createForm(AdminUserSettingsType::class, $adminUser, ['is_edit' => $isEdit]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            if ($plainPassword !== '') {
                $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, $plainPassword));
            } elseif (! $isEdit) {
                $this->addFlash('error', 'Пароль обовʼязковий для нового адміна.');

                return $this->redirectToRoute('admin2_settings', ['tab' => 'admins']);
            }

            if ($adminUser->getRoles() === []) {
                $adminUser->setRoles([AdminUser::ROLE_USER]);
            }

            if (! $isEdit) {
                $this->entityManager->persist($adminUser);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isEdit
                ? sprintf('Адміна «%s» збережено.', $adminUser->getEmail())
                : sprintf('Адміна «%s» створено.', $adminUser->getEmail()));
        } else {
            $this->addFlash('error', 'Перевірте правильність полів адміна.');
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'admins']);
    }
}
