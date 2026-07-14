<?php

namespace App\Controller\Admin2;

use App\Entity\FopProfile;
use App\Form\Admin2\FopProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
final class SettingsFopProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/settings/fops', name: 'admin2_settings_fops_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        return $this->handleSave($request, new FopProfile(), false);
    }

    #[Route('/admin2/settings/fops/{id}', name: 'admin2_settings_fops_update', methods: ['POST'])]
    public function update(Request $request, int $id): Response
    {
        $profile = $this->entityManager->getRepository(FopProfile::class)->find($id);
        if (! $profile instanceof FopProfile) {
            throw $this->createNotFoundException('ФОП не знайдено.');
        }

        return $this->handleSave($request, $profile, true);
    }

    #[Route('/admin2/settings/fops/{id}/delete', name: 'admin2_settings_fops_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_fop', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'fops']);
        }

        $profile = $this->entityManager->getRepository(FopProfile::class)->find($id);
        if ($profile instanceof FopProfile) {
            $title = $profile->getTitle();
            $this->entityManager->remove($profile);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('ФОП «%s» видалено.', $title));
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'fops']);
    }

    private function handleSave(Request $request, FopProfile $profile, bool $isEdit): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_fop', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'fops']);
        }

        $form = $this->createForm(FopProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! $isEdit) {
                $this->entityManager->persist($profile);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isEdit
                ? sprintf('ФОП «%s» збережено.', $profile->getTitle())
                : sprintf('ФОП «%s» створено.', $profile->getTitle()));
        } else {
            $this->addFlash('error', 'Перевірте правильність полів ФОП.');
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'fops']);
    }
}

