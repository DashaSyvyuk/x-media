<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FilterParameterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CategoryPageController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private FilterParameterRepository $filterParameterRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param FilterParameterRepository $filterParameterRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        FilterParameterRepository $filterParameterRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->filterParameterRepository = $filterParameterRepository;
    }

    public function get(string $slug): Response
    {
        $category = $this->categoryRepository->findOneBy([
            'slug' => $slug
        ]);

        if (!$category) {
            $this->render('/not-found.html.twig');
        }

        $categories = $this->categoryRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'position' => 'ASC'
        ]);

        $filters = $this->filterParameterRepository->findByCategory($slug);

        return $this->render('category_page/index.html.twig', [
            'category' => $category,
            'categories' => $categories,
            'filters' => $filters
        ]);
    }
}
