<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Response;

class DeliveryAndPayController extends BaseController
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

    public function index(): Response
    {
        return $this->renderTemplate('delivery_and_pay_page/index.html.twig', []);
    }
}
