<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends BaseController
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

    public function post(Request $request): Response
    {
        $session = $request->getSession();

        $email = $session->get('user');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        $password = $request->request->get('password');

        $passwordConfirm = $request->request->get('passwordConfirm');

        if ($password && !password_verify($password, $user->getPassword())) {
            if ($password != $passwordConfirm) {
                return new Response(json_encode([
                    'error' => 'Паролі не співпадають'
                ]));
            }

            if (strlen($password)) {
                return new Response(json_encode([
                    'error' => 'Мінімальна довжина пароля 6 символів'
                ]));
            }
        }

        $user->setName($request->request->get('name'));
        $user->setSurname($request->request->get('surname'));
        $user->setPassword($password);
        $user->setNovaPoshtaCity($request->request->get('city'));
        $user->setNovaPoshtaOffice($request->request->get('office'));

        $this->userRepository->update($user);

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
