<?php

namespace App\Controller\Admin2;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\EventListener\ProductImageUploadSubscriber;
use App\Form\Admin2\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Security("is_granted('ROLE_USER')")]
class ProductEditController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductImageUploadSubscriber $productImageUploadSubscriber,
    ) {
    }

    #[Route('/admin/products/new', name: 'admin2_products_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->productImageUploadSubscriber->ensureLocalDir();

        $product = new Product();
        $product->setStatus(Product::STATUS_ACTIVE);
        $product->setAvailability(Product::AVAILABILITY_TO_ORDER);
        $product->setPrice(1);
        $product->setDescription('');
        $product->setProductCode('');

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->scrubEmptyImages($product);

            try {
                $this->entityManager->persist($product);
                $this->entityManager->flush();
            } catch (Throwable $e) {
                $this->addFlash('error', 'Не вдалося зберегти товар: ' . $e->getMessage());

                return $this->render('admin2/products/edit.html.twig', [
                    'product' => $product,
                    'form'    => $form,
                    'isNew'   => true,
                ]);
            }

            $this->addFlash('success', sprintf('Товар #%d створено.', $product->getId()));

            return $this->redirectToRoute('admin2_products_edit', ['id' => $product->getId()]);
        }

        return $this->render('admin2/products/edit.html.twig', [
            'product' => $product,
            'form'    => $form,
            'isNew'   => true,
        ]);
    }

    #[Route('/admin/products/{id}/edit', name: 'admin2_products_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $this->productImageUploadSubscriber->ensureLocalDir();

        $product = $this->productRepository->find($id);
        if (! $product instanceof Product) {
            throw $this->createNotFoundException('Товар не знайдено.');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->scrubEmptyImages($product);

            try {
                $this->entityManager->flush();
            } catch (Throwable $e) {
                $this->addFlash('error', 'Не вдалося зберегти товар: ' . $e->getMessage());

                return $this->render('admin2/products/edit.html.twig', [
                    'product' => $product,
                    'form'    => $form,
                    'isNew'   => false,
                ]);
            }

            $this->addFlash('success', sprintf('Товар #%d збережено.', $product->getId()));

            return $this->redirectToRoute('admin2_products_edit', ['id' => $product->getId()]);
        }

        return $this->render('admin2/products/edit.html.twig', [
            'product' => $product,
            'form'    => $form,
            'isNew'   => false,
        ]);
    }

    private function scrubEmptyImages(Product $product): void
    {
        foreach ($product->getImages()->toArray() as $image) {
            if (! $image instanceof ProductImage) {
                continue;
            }

            $hasFile = $image->getFile() !== null;
            $hasUrl  = $image->getImageUrl() !== null && $image->getImageUrl() !== '';
            if (! $hasFile && ! $hasUrl) {
                $product->removeImage($image);
            }
        }
    }
}
