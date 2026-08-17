<?php

namespace App\Service\Admin2;

use App\Entity\Product;
use App\Entity\ProductCharacteristic;
use App\Entity\ProductFilterAttribute;
use App\Repository\ProductRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

readonly class ProductCloneService
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function clone(int $productId): ?Product
    {
        $source = $this->productRepository->find($productId);
        if ($source === null) {
            return null;
        }

        $clone = new Product();
        $clone->setTitle(sprintf('%s (Copy)', $source->getTitle()));
        $clone->setStatus($source->getStatus());
        $clone->setAvailability($source->getAvailability());
        $clone->setPrice($source->getPrice());
        $clone->setCrossedOutPrice($source->getCrossedOutPrice());
        $clone->setDescription((string) ($source->getDescription() ?? ''));
        $clone->setNote($source->getNote());
        $clone->setMetaKeyword((string) ($source->getMetaKeyword() ?? ''));
        $clone->setMetaDescription((string) ($source->getMetaDescription() ?? ''));
        $clone->setProductCode($source->getProductCode());
        $clone->setProductCode2($source->getProductCode2());
        $clone->setOlx($source->getOlx());
        $clone->setXkomUrl($source->getXkomUrl());
        $clone->setRozetka(null);
        $clone->setCreatedAt(new DateTime('now'));
        $clone->setUpdatedAt(new DateTime('now'));

        if ($source->getCategory() !== null) {
            $clone->setCategory($source->getCategory());
        }

        foreach ($source->getCharacteristics() as $characteristic) {
            $copy = new ProductCharacteristic();
            $copy->setTitle($characteristic->getTitle());
            $copy->setValue($characteristic->getValue());
            $copy->setPosition($characteristic->getPosition());
            $clone->addCharacteristic($copy);
        }

        foreach ($source->getFilterAttributes() as $filterAttribute) {
            $filter = $filterAttribute->getFilter();
            $attribute = $filterAttribute->getFilterAttribute();
            if ($filter === null || $attribute === null) {
                continue;
            }

            $copy = new ProductFilterAttribute();
            $copy->setFilter($filter);
            $copy->setFilterAttribute($attribute);
            $clone->addFilterAttribute($copy);
        }

        $this->entityManager->persist($clone);
        $this->entityManager->flush();

        return $clone;
    }
}
