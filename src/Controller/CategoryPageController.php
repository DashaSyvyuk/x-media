<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FilterRepository;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CategoryPageController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private FilterRepository $filterRepository;

    private ProductRepository $productRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param FilterRepository $filterRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        FilterRepository $filterRepository,
        ProductRepository $productRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->filterRepository = $filterRepository;
        $this->productRepository = $productRepository;
    }

    public function getCategory(string $slug, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $this->getFilters($request->query->get('filters'));

        $category = $this->categoryRepository->findOneBy([
            'slug' => $slug
        ]);

        $categories = $this->categoryRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'position' => 'ASC'
        ]);

        if (!$category) {
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [
                'totalCount' => $_COOKIE['totalCount'],
                'categories' => $categories
            ]);
        }

        $filters = $this->filterRepository->findByCategory($slug);

        $products = $this->productRepository->findByCategoryAndAttributes($category, $query);

        $pagination = $paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            20
        );

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode([
                'products' => $this->renderView('partials/product-list.html.twig', [
                    'pagination' => $pagination
                ])
            ]));
        } else {
            return $this->render('category_page/index.html.twig', [
                'category' => $category,
                'categories' => $categories,
                'filters' => $filters,
                'pagination' => $pagination,
                'query' => $query,
                'totalCount' => $_COOKIE['totalCount'],
            ]);
        }
    }

    private function getFilters($filters)
    {
        $attributes = explode(';', $filters);

        return !empty($attributes[0]) ? $attributes : [];
    }
}
