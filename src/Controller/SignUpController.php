<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SignUpController extends BaseController
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
        return $this->renderTemplate('sign_up/index.html.twig', []);
    }

    public function post(Request $request): Response
    {
        if ($this->userRepository->findOneBy(['email' => $request->request->get('email')])) {
            return new Response(json_encode([
                'error' => 'Email already exists'
            ]));
        }

        $user = new User();
        $user->setEmail($request->request->get('email'));
        $user->setName($request->request->get('name'));
        $user->setSurname($request->request->get('surname'));
        $user->setPhone($request->request->get('phone'));
        $user->setPassword($request->request->get('password'));
        $user->setConfirmed(false);

        $this->userRepository->create($user);

        $session = $request->getSession();

        $session->set('user', $request->request->get('email'));

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
