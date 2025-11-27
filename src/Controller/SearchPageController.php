<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductFilterAttributeRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SearchPageController extends BaseController
{
    public const VENDOR_FILTER_TITLE = 'Марка';

    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param ProductFilterAttributeRepository $productFilterAttributeRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly SettingRepository $settingRepository,
        private readonly ProductFilterAttributeRepository $productFilterAttributeRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
        ini_set('memory_limit', '512M');
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function getSearch(?string $categorySlug, PaginatorInterface $paginator, Request $request): Response
    {
        $order = $request->query->get('order') ?? 'createdAt';
        $direction = $request->query->get('direction') ?? 'desc';
        $limit = $this->settingRepository->findOneBy(['slug' => 'pagination_limit']);
        $vendors = $request->query->get('vendors') ?? '';
        $search = $request->query->get('search');
        $category = null;
        $categoryTree = [];

        if ($categorySlug) {
            $category = $this->categoryRepository->findOneBy(['slug' => $categorySlug, 'status' => 'ACTIVE']);
            if (!$category) {
                throw new Exception('Category not found');
            }
        }

        $products = $this->productRepository->findBySearch($search, $vendors, $order, $direction);
        $pagination = $paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            intval($limit->getValue())
        );

        $categories = $this->categoryRepository->getCategoriesTree();
        $categoriesTree = $this->productRepository->getCategoriesTreeForSearch($categories, $search);

        if ($category) {
            $currentCategory = array_filter($categoriesTree, fn ($item) => $item['id'] == $category->getId());
            if (!empty($currentCategory)) {
                $curCat = reset($currentCategory);
                if (!empty($curCat['children'])) {
                    $categoryTree = $curCat['children'];
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode([
                'products' => $this->renderView('partials/product-list.html.twig', [
                    'pagination' => $pagination
                ])
            ]));
        } else {
            return $this->renderTemplate($request, 'search_page/index.html.twig', [
                'pagination'      => $pagination,
                'searchString'    => $search,
                'categoriesTree'  => $categoriesTree,
                'categoryTree'    => $categoryTree,
                'currentCategory' => $category,
                'vendors'         => explode(',', $vendors),
                'order'           => $order,
                'direction'       => $direction,
                'filters'         => $this->productFilterAttributeRepository->findFilterParameterByTitleAndSearchString(
                    self::VENDOR_FILTER_TITLE,
                    $search,
                    $category
                )
            ]);
        }
    }
}
