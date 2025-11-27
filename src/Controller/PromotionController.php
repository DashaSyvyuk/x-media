<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductFilterAttributeRepository;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Repository\SettingRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class PromotionController extends BaseController
{
    public const VENDOR_FILTER_TITLE = 'Марка';

    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param PromotionRepository $promotionRepository
     * @param ProductFilterAttributeRepository $productFilterAttributeRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly SettingRepository $settingRepository,
        private readonly PromotionRepository $promotionRepository,
        private readonly ProductFilterAttributeRepository $productFilterAttributeRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function getPromotion(
        string $slug,
        ?string $categorySlug,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $order = $request->query->get('order') ?? 'createdAt';
        $direction = $request->query->get('direction') ?? 'desc';
        $limit = $this->settingRepository->findOneBy(['slug' => 'pagination_limit']);
        $vendors = $request->query->get('vendors') ?? '';
        $category = null;
        $categoryTree = [];

        $promotion = $this->promotionRepository->getActivePromotionBySlug($slug);

        if (!$promotion) {
            throw new Exception('Promotion isn\'t active any more');
        }

        if ($categorySlug) {
            $category = $this->categoryRepository->findOneBy(['slug' => $categorySlug, 'status' => 'ACTIVE']);
            if (!$category) {
                throw new Exception('Category not found');
            }
        }

        $products = $this->productRepository->findByPromotionAndVendor(
            $promotion,
            $category,
            $vendors,
            $order,
            $direction
        );

        $pagination = $paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            intval($limit->getValue())
        );

        $categories = $this->categoryRepository->getCategoriesTree();
        $categoriesTree = $this->productRepository->getCategoriesTreeForPromotion($categories, $promotion);

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
                ]),
            ]));
        } else {
            return $this->renderTemplate($request, 'promotion_page/index.html.twig', [
                'promotion'       => $promotion,
                'filters'         => $this->productFilterAttributeRepository->findFilterParameterByTitle(
                    self::VENDOR_FILTER_TITLE,
                    $promotion,
                    $category
                ),
                'pagination'      => $pagination,
                'order'           => $order,
                'direction'       => $direction,
                'categoriesTree'  => $categoriesTree,
                'categoryTree'    => $categoryTree,
                'currentCategory' => $category,
                'vendors'         => explode(',', $vendors),
            ]);
        }
    }
}
