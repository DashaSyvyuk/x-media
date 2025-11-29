<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends BaseController
{
    public const string CART_TEMPLATE = 'cart/index.html.twig';

    public function getCart(): Response
    {
        if (!array_key_exists('cart', $_COOKIE)) {
            return new Response(json_encode([
                'cart' => $this->renderView(self::CART_TEMPLATE, [
                    'products'   => [],
                    'totalCount' => 0,
                    'totalPrice' => 0
                ])
            ]));
        }

        $cart = $this->getTotalCart();

        return new Response(json_encode([
            'cart' => $this->renderView(self::CART_TEMPLATE, [
                'products'   => $cart['products'],
                'totalCount' => $cart['totalCount'],
                'totalPrice' => $cart['totalPrice']
            ]),
            'totalCount' => $cart['totalCount'],
        ]));
    }

    public function getMobileCart(Request $request): Response
    {
        if (!array_key_exists('cart', $_COOKIE)) {
            return $this->renderTemplate($request, self::CART_TEMPLATE, [
                'products'   => [],
                'totalCount' => 0,
                'totalPrice' => 0
            ]);
        }

        $cart = $this->getTotalCart();

        return $this->renderTemplate($request, self::CART_TEMPLATE, [
            'products'   => $cart['products'],
            'totalCount' => $cart['totalCount'],
            'totalPrice' => $cart['totalPrice']
        ]);
    }
}
