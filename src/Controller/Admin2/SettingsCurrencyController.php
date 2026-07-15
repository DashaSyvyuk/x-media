<?php

namespace App\Controller\Admin2;

use App\Entity\Currency;
use App\Form\Admin2\CurrencyType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class SettingsCurrencyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/settings/currencies', name: 'admin2_settings_currencies_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        return $this->handleSave($request, new Currency(), false);
    }

    #[Route('/admin2/settings/currencies/{id}', name: 'admin2_settings_currencies_update', methods: ['POST'])]
    public function update(Request $request, int $id): Response
    {
        $currency = $this->entityManager->getRepository(Currency::class)->find($id);
        if (! $currency instanceof Currency) {
            throw $this->createNotFoundException('Валюту не знайдено.');
        }

        return $this->handleSave($request, $currency, true);
    }

    #[Route('/admin2/settings/currencies/{id}/delete', name: 'admin2_settings_currencies_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_currency', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'currencies']);
        }

        $currency = $this->entityManager->getRepository(Currency::class)->find($id);
        if ($currency instanceof Currency) {
            $title = $currency->getTitle();
            $this->entityManager->remove($currency);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Валюту «%s» видалено.', $title));
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'currencies']);
    }

    private function handleSave(Request $request, Currency $currency, bool $isEdit): Response
    {
        if (! $this->isCsrfTokenValid('admin2_settings_currency', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'currencies']);
        }

        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! $isEdit) {
                $this->entityManager->persist($currency);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isEdit
                ? sprintf('Валюту «%s» збережено.', $currency->getTitle())
                : sprintf('Валюту «%s» створено.', $currency->getTitle()));
        } else {
            $this->addFlash('error', 'Перевірте правильність полів валюти.');
        }

        return $this->redirectToRoute('admin2_settings', ['tab' => 'currencies']);
    }
}
