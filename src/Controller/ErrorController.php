<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends BaseController
{
    public function show(Request $request): Response
    {
        return $this->renderTemplate($request,'bundles/TwigBundle/Exception/error404.html.twig', []);
    }
}
