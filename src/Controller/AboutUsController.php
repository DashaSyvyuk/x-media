<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\SliderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AboutUsController extends AbstractController
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

    public function index(): Response
    {
        return $this->render('about_us_page/index.html.twig', [
            'categories'   => $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']),
            'totalCount'   => $_COOKIE['totalCount'] ?? 0,
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails'       => $this->settingRepository->findBy(['slug' => 'email'])
        ]);
    }
}
