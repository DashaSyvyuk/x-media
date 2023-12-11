<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SearchPageController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly SettingRepository $settingRepository
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function getSearch(PaginatorInterface $paginator, Request $request): Response
    {
        $limit = $this->settingRepository->findOneBy(['slug' => 'pagination_limit']);

        $query = $request->query->get('search');

        $products = $this->productRepository->findBySearch($query);

        $pagination = $paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            $limit->getValue()
        );

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode([
                'products' => $this->renderView('partials/product-list.html.twig', [
                    'pagination' => $pagination
                ])
            ]));
        } else {
            return $this->renderTemplate($request, 'search_page/index.html.twig', [
                'pagination' => $pagination,
                'searchString' => $query
            ]);
        }
    }
}
