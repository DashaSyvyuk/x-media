<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'contact_page/index.html.twig', []);
    }
}
