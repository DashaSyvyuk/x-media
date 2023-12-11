<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WarrantyController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'warranty_page/index.html.twig', []);
    }
}
