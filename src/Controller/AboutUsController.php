<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AboutUsController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'about_us_page/index.html.twig', []);
    }
}
