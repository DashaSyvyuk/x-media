<?php

namespace App\Controller\Admin;

use App\Entity\RozetkaProduct;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\RozetkaProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ControlPriceController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function index(): Response
    {
        $products = $this->productRepository->findAll();
        $categories = $this->categoryRepository->findAll();

        return $this->render('control_price/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function update(
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf,
        RozetkaProductRepository $rozetkaRepository,
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid JSON'], 400);
        }

        $id = (int)($data['id'] ?? 0);
        $token = (string)($data['_token'] ?? '');

        if ($id <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid id'], 400);
        }

        if (!$csrf->isTokenValid(new CsrfToken('product_inline_update', $token))) {
            return new JsonResponse(['ok' => false, 'error' => 'CSRF invalid'], 403);
        }

        $product = $this->productRepository->find($id);
        if (!$product) {
            return new JsonResponse(['ok' => false, 'error' => 'Product not found'], 404);
        }

        // --- status ---
        $status = (string)($data['status'] ?? '');
        if (!in_array($status, ['Активний', 'Заблокований'], true)) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid status'], 400);
        }

        // --- price ---
        $price = $this->toNullableInt($data['price'] ?? null);
        $oldPrice = $this->toNullableInt($data['crossed_out_price'] ?? null);

        if ($price === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Price is required'], 400);
        }
        if ($price < 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Price must be >= 0'], 400);
        }
        if ($oldPrice !== null && $oldPrice < 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Crossed price must be >= 0'], 400);
        }

        // --- rozetka ---
        $rzPrice = $this->toNullableInt($data['rozetka_price'] ?? null);
        $rzOld = $this->toNullableInt($data['rozetka_crossed_out_price'] ?? null);

        if ($rzPrice !== null && $rzPrice < 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Rozetka price must be >= 0'], 400);
        }
        if ($rzOld !== null && $rzOld < 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Rozetka crossed price must be >= 0'], 400);
        }

        $rozetka = $rozetkaRepository->findOneBy(['product' => $product]);

//        if (!$rozetka && ($rzPrice !== null || $rzOld !== null)) {
//            $rozetka = new RozetkaProduct();
//            $rozetka->setProduct($product);
//            $em->persist($rozetka);
//        }

        $product->setStatus($status);
        $product->setPrice($price);
        $product->setCrossedOutPrice($oldPrice);

        if ($rozetka) {
            $rozetka->setPrice($rzPrice);
            $rozetka->setCrossedOutPrice($rzOld);
        }

        $em->flush();

        return new JsonResponse(['ok' => true]);
    }

    private function toNullableInt(mixed $v): ?int
    {
        if ($v === null) {
            return null;
        }

        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') {
                return null;
            }
        }

        if (!is_numeric($v)) {
            return null;
        }

        return (int)$v;
    }
}
