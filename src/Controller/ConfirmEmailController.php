<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfirmEmailController extends BaseController
{
  /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param ProductRepository $productRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        $hash = $request->query->get('hash');
        $user = $this->userRepository->findOneBy(['hash' => $hash]);

        if (!$user || $user->getExpiredAt() < Carbon::now()) {
            return $this->redirectToRoute('index');
        } else {
            $user->setConfirmed(true);
            $this->userRepository->update($user);
            return $this->redirectToRoute('login');
        }
    }
}
