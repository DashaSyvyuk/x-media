<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
    public function __construct() {

    }

    public function getCart(): Response
    {
        return new Response(json_encode([
            'cart' => $this->renderView('cart/index.html.twig', [
                'products' => json_decode($_COOKIE['cart'])
            ])
        ]));
    }
}
