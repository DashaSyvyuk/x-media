<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Legacy public URL kept for bookmarks — UI moved to admin2 Rozetka.
 */
class UploadCharacteristicsController extends AbstractController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin2_rozetka');
    }

    public function upload(): Response
    {
        return $this->redirectToRoute('admin2_rozetka');
    }
}
