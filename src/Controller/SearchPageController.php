<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SearchPageController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private ProductRepository $productRepository;

    private SettingRepository $settingRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->settingRepository = $settingRepository;
    }

    public function getSearch(PaginatorInterface $paginator, Request $request): Response
    {
        $categories = $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']);

        $query = $request->query->get('search');

        $products = $this->productRepository->findBySearch($query);

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
            return $this->render('search_page/index.html.twig', [
                'categories' => $categories,
                'pagination' => $pagination,
                'totalCount' => $_COOKIE['totalCount'] ?? 0,
                'searchString' => $query,
                'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
                'emails' => $this->settingRepository->findBy(['slug' => 'email'])
            ]);
        }
    }
}
