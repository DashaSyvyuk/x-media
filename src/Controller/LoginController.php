<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly UserRepository $userRepository,
        private readonly ProductRepository $productRepository
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'login/index.html.twig', []);
    }

    public function post(Request $request): Response
    {
        $password = $request->request->get('password');

        $user = $this->userRepository->findOneBy([
            'email' => $request->request->get('email'),
            'confirmed' => true
        ]);

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
