<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    public function show(): Response
    {
        $categories = $this->categoryRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'position' => 'ASC'
        ]);

        return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [
            'categories' => $categories,
            'totalCount' => $_COOKIE['totalCount'] ?? 0
        ]);
    }
}
