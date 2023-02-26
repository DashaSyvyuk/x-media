<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountContactController extends BaseController
{
    private NovaPoshtaCityRepository $novaPoshtaCityRepository;

    private UserRepository $userRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param NovaPoshtaCityRepository $novaPoshtaCityRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository,
        NovaPoshtaCityRepository $novaPoshtaCityRepository,
        UserRepository $userRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->novaPoshtaCityRepository = $novaPoshtaCityRepository;
        $this->userRepository = $userRepository;
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

        return $this->renderTemplate('account_contact/index.html.twig', [
            'cities' => $this->novaPoshtaCityRepository->getCitiesWithOffices(),
            'offices' => $user->getNovaPoshtaCity() && $city ? $city->getOffices() : null,
            'user' => $user
        ]);
    }
}
