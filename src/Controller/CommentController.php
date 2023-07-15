<?php

namespace App\Controller;

use App\Service\Comment\CommentCreateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends AbstractController
{
    public function __construct(
        private readonly CommentCreateService $commentCreateService,
    ) {
    }

    public function post(Request $request): Response
    {
        try {
            $this->commentCreateService->run($request->request->all());

            return new Response(json_encode([
                'success' => true
            ]));
        } catch (\Exception $e) {
            return new Response(json_encode([
                'success' => false
            ]));
        }
    }
}
