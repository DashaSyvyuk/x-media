<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends BaseController
{
    public function post(Request $request): Response
    {
        $session = $request->getSession();

        $session->clear();

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
