<?php

namespace App\Controller\Admin2;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the admin service worker under /admin/* so its scope can cover the new admin.
 * Must not live as public/admin/ — that directory makes nginx return 403 for /admin/.
 */
class PwaController extends AbstractController
{
    #[Route('/admin/sw.js', name: 'admin2_service_worker', methods: ['GET'])]
    public function serviceWorker(): Response
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/admin2/sw.js';
        if (! is_readable($path)) {
            throw $this->createNotFoundException();
        }

        return new Response(
            (string) file_get_contents($path),
            Response::HTTP_OK,
            [
                'Content-Type'           => 'application/javascript; charset=UTF-8',
                'Service-Worker-Allowed' => '/admin/',
                'Cache-Control'          => 'no-cache',
            ],
        );
    }
}
