<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryAndPayController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'delivery_and_pay_page/index.html.twig', []);
    }
}
