<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfirmEmailController extends BaseController
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
