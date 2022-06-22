<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(CategoryRepository $categoryRepository) {
        $this->categoryRepository = $categoryRepository;
    }

    public function renderTemplate(string $view, array $parameters): Response
    {
        return $this->render($view, array_merge($parameters, [
            'categories' => $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']),
            'totalCount' => $_COOKIE['totalCount'] ?? 0
        ]));
    }
}
