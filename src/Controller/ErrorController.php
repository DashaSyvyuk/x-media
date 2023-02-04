<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
    }

    public function show(): Response
    {
        return $this->renderTemplate('bundles/TwigBundle/Exception/error404.html.twig', [
            'totalCount' => $_COOKIE['totalCount'] ?? 0
        ]);
    }
}
