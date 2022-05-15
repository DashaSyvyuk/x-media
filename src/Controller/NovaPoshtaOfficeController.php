<?php

namespace App\Controller;

use App\Repository\NovaPoshtaOfficeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NovaPoshtaOfficeController extends AbstractController
{
    private NovaPoshtaOfficeRepository $repository;

    /**
     * @param NovaPoshtaOfficeRepository $repository
     */
    public function __construct(NovaPoshtaOfficeRepository $repository) {
        $this->repository = $repository;
    }

    public function index(Request $request): Response
    {
        $cityRef = $request->query->get('cityRef');

        return new Response(json_encode($this->repository->getOfficesByCityRef($cityRef)));
    }
}
