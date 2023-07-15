<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use App\Service\Cart\CartShowService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends BaseController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly CartShowService $cartShowService
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository);
    }

    public function getCart(): Response
    {
        $cart = $this->cartShowService->run($_COOKIE['cart']);

        return new Response(json_encode([
            'cart' => $this->renderView('cart/index.html.twig', [
                'products'   => $cart['products'],
                'totalCount' => $cart['totalCount'],
                'totalPrice' => $cart['totalPrice']
            ])
        ]));
    }

    public function getMobileCart(Request $request): Response
    {
        $cart = $this->cartShowService->run($_COOKIE['cart']);

        return $this->renderTemplate($request, 'cart/index.html.twig', [
            'products'   => $cart['products'],
            'totalCount' => $cart['totalCount'],
            'totalPrice' => $cart['totalPrice']
        ]);
    }
}
