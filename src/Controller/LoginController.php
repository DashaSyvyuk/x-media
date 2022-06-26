<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends BaseController
{
    private UserRepository $userRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository,
        UserRepository $userRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->userRepository = $userRepository;
    }

    public function index(): Response
    {
        return $this->renderTemplate('login/index.html.twig', []);
    }

    public function post(Request $request): Response
    {
        $password = $request->request->get('password');
        $user = $this->userRepository->findOneBy(['email' => $request->request->get('email')]);

        if (!$user) {
            return new Response(json_encode([
                'error' => 'Користувача з даним email не існує'
            ]));
        }

        if (!password_verify($password, $user->getPassword())) {
            return new Response(json_encode([
                'error' => 'Пароль або email невірні'
            ]));
        }

        $session = $request->getSession();

        $session->set('user', $request->request->get('email'));

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
