<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Response;

class DeliveryAndPayController extends BaseController
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

    public function index(): Response
    {
        return $this->renderTemplate('delivery_and_pay_page/index.html.twig', [
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails' => $this->settingRepository->findBy(['slug' => 'email'])
        ]);
    }
}
