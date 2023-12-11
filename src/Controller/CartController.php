<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends BaseController
{
    public function getCart(): Response
    {
        if (!array_key_exists('cart', $_COOKIE)) {
            return new Response(json_encode([
                'cart' => $this->renderView('cart/index.html.twig', [
                    'products'   => [],
                    'totalCount' => 0,
                    'totalPrice' => 0
                ])
            ]));
        }

        $cart = $this->getTotalCart();

        return new Response(json_encode([
            'cart' => $this->renderView('cart/index.html.twig', [
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
            return $this->renderTemplate($request, 'cart/index.html.twig', [
                'products'   => [],
                'totalCount' => 0,
                'totalPrice' => 0
            ]);
        }

        $cart = $this->getTotalCart();

        return $this->renderTemplate($request, 'cart/index.html.twig', [
            'products'   => $cart['products'],
            'totalCount' => $cart['totalCount'],
            'totalPrice' => $cart['totalPrice']
        ]);
    }
}
