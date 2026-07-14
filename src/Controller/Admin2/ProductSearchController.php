<?php

namespace App\Controller\Admin2;

use App\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class ProductSearchController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    #[Route('/admin2/api/products/search', name: 'admin2_api_products_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        if ($query === '') {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->productRepository->searchForAdminPicker($query));
    }

    #[Route('/admin2/api/products/{id}', name: 'admin2_api_products_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->findAdminPickerItem($id);
        if ($product === null) {
            return new JsonResponse(['error' => 'Товар не знайдено.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($product);
    }
}
