<?php

namespace App\Controller\Admin2;

use App\Entity\AdminUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin2_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        /** @var AdminUser $user */
        $user = $this->getUser();

        return $this->render('admin2/dashboard/index.html.twig', [
            'user' => $user,
        ]);
    }
}
