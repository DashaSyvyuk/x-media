<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThankYouController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly SettingRepository $settingRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        if (isset($_COOKIE['orderId'])) {
            $orderId = $_COOKIE['orderId'];
            $order = $this->orderRepository->findOneBy(['id' => $orderId]);

            if (!$order) {
                return $this->redirectToRoute('index');
            }

            unset($_COOKIE['orderId']);
            setcookie('orderId', '', -1, '/');

            return $this->renderTemplate($request, 'thank_page/index.html.twig', [
                'order' => $order,
            ]);
        } else {
            return $this->redirectToRoute('index');
        }
    }
}
