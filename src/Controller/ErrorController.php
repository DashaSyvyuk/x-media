<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private SettingRepository $settingRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->settingRepository = $settingRepository;
    }

    public function show(): Response
    {
        $categories = $this->categoryRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'position' => 'ASC'
        ]);

        $phoneNumbers = $this->settingRepository->findBy([
            'slug' => 'phone_number'
        ]);

        $emails = $this->settingRepository->findBy([
            'slug' => 'email'
        ]);

        return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [
            'categories' => $categories,
            'totalCount' => $_COOKIE['totalCount'] ?? 0,
            'phoneNumbers' => $phoneNumbers,
            'emails' => $emails
        ]);
    }
}
