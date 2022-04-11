<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FilterRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CategoryPageController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private FilterRepository $filterRepository;

    private ProductRepository $productRepository;

    private SettingRepository $settingRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param FilterRepository $filterRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        FilterRepository $filterRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->filterRepository = $filterRepository;
        $this->productRepository = $productRepository;
        $this->settingRepository = $settingRepository;
    }

    public function getCategory(string $slug, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $this->getFilters($request->query->get('filters'));
        $order = $request->query->get('order');
        $direction = $request->query->get('direction');
        $priceFrom = $request->query->get('price_from');
        $priceTo = $request->query->get('price_to');
        $limit = $this->settingRepository->findOneBy(['slug' => 'pagination_limit']);

        $category = $this->categoryRepository->findOneBy(['slug' => $slug, 'status' => 'ACTIVE']);

        $categories = $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']);

        if (!$category) {
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [
                'totalCount' => $_COOKIE['totalCount'] ?? 0,
                'categories' => $categories
            ]);
        }

        $filterSetting = $this->settingRepository->findOneBy([
            'slug' => 'filter_attribute_count'
        ]);

        $products = $this->productRepository->findByCategoryAndAttributes(
            $category, $query, $order, $direction, $priceFrom, $priceTo
        );

        $pagination = $paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            $limit->getValue()
        );

        $prices = $this->productRepository->getMinAndMaxPriceInCategory($category, $query, $priceFrom, $priceTo);

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode([
                'products' => $this->renderView('partials/product-list.html.twig', [
                    'pagination' => $pagination
                ]),
                'minPrice' => $prices['min_price'],
                'maxPrice' => $prices['max_price']
            ]));
        } else {
            return $this->render('category_page/index.html.twig', [
                'category' => $category,
                'categories' => $categories,
                'filters' => $this->filterRepository->findByCategory($slug),
                'pagination' => $pagination,
                'query' => $query,
                'totalCount' => $_COOKIE['totalCount'] ?? 0,
                'filterCount' => $filterSetting ? $filterSetting->getValue() : null,
                'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
                'emails' => $this->settingRepository->findBy(['slug' => 'email']),
                'order' => $order,
                'direction' => $direction,
                'minPrice' => $prices['min_price'],
                'maxPrice' => $prices['max_price']
            ]);
        }
    }

    private function getFilters($filters)
    {
        $attributes = explode(';', $filters);

        return !empty($attributes[0]) ? $attributes : [];
    }
}
