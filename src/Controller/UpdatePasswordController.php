<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdatePasswordController extends BaseController
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
        private readonly ProductRepository $productRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        $hash = $request->query->get('hash');
        $user = $this->userRepository->findOneBy(['hash' => $hash]);

        if (!$user || $user->getExpiredAt() < Carbon::now()) {
            return $this->redirectToRoute('index');
        }

        return $this->renderTemplate($request, 'update_password/index.html.twig', []);
    }

    public function post(Request $request): Response
    {
        $password = $request->request->get('password');
        $hash = $request->request->get('hash');

        $user = $this->userRepository->findOneBy(['hash' => $hash]);

        if (!$user || $user->getExpiredAt() < Carbon::now()) {
            return new Response(json_encode([
                'error' => 'Для зміни паролю потрібно згенерувати нове посилання'
            ]));
        }

        $user->setPassword($password);
        $this->userRepository->update($user);

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
