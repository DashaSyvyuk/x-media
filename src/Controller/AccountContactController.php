<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountContactController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param ProductRepository $productRepository
     * @param NovaPoshtaCityRepository $novaPoshtaCityRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly ProductRepository $productRepository,
        private readonly NovaPoshtaCityRepository $novaPoshtaCityRepository,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        $session = $request->getSession();

        $email = $session->get('user');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->redirectToRoute('index');
        }

        $city = null;
        if ($user->getNovaPoshtaCity()) {
            $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $user->getNovaPoshtaCity()]);
        }

        return $this->renderTemplate($request, 'account_contact/index.html.twig', [
            'cities' => $this->novaPoshtaCityRepository->getCitiesWithOffices(),
            'offices' => $user->getNovaPoshtaCity() && $city ? $city->getOffices() : null,
            'user' => $user
        ]);
    }
}
