<?php

namespace App\Controller\API;

use App\Controller\BaseController;
use App\Repository\CategoryRepository;
use App\Repository\DeliveryTypeRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryTypeController extends BaseController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly ProductRepository $productRepository,
        private readonly DeliveryTypeRepository $deliveryTypeRepository,
        private readonly UserRepository $userRepository,
        private readonly NovaPoshtaCityRepository $novaPoshtaCityRepository
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function getItem(int $id, Request $request): Response
    {
        $city = null;
        $user = null;
        $deliveryType = $this->deliveryTypeRepository->findOneBy(['id' => $id]);

        if (!$deliveryType) {
            return new Response('Not found', 404);
        }

        $session = $request->getSession();

        if ($email = $session->get('user')) {
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user->getNovaPoshtaCity()) {
                $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $user->getNovaPoshtaCity()]);
            }
        }

        return new Response(json_encode([
            'template' => $this->renderView('partials/delivery-type.html.twig', [
                'deliveryType' => $deliveryType,
                'cities' => $this->novaPoshtaCityRepository->getCitiesWithOffices(),
                'offices' => $city ? $city->getOffices() : [],
                'user' => $user,
            ]),
        ]));
    }
}
