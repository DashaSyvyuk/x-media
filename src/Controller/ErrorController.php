<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends BaseController
{
    private SettingRepository $settingRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository
    ) {
        parent::__construct($categoryRepository);
        $this->settingRepository = $settingRepository;
    }

    public function show(): Response
    {
        return $this->renderTemplate('bundles/TwigBundle/Exception/error404.html.twig', [
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails' => $this->settingRepository->findBy(['slug' => 'email'])
        ]);
    }
}
