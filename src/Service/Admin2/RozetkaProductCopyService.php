<?php

namespace App\Service\Admin2;

use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaProduct;
use App\Repository\RozetkaProductRepository;

final readonly class RozetkaProductCopyService
{
    public function __construct(
        private RozetkaProductRepository $rozetkaProductRepository,
    ) {
    }

    public function copyCharacteristics(RozetkaProduct $target, ?RozetkaProduct $source): bool
    {
        if ($source === null) {
            return false;
        }

        $target->setSeries($source->getSeries());
        $target->setDescription($source->getDescription());

        if ($source->getValues()->isEmpty()) {
            return true;
        }

        foreach ($target->getValues()->toArray() as $value) {
            $target->removeValue($value);
        }

        foreach ($source->getValues() as $value) {
            $productValue = new ProductRozetkaCharacteristicValue();
            $productValue->setRozetkaProduct($target);
            $productValue->setCharacteristic($value->getCharacteristic());
            $productValue->setValue($value->getValue());
            $productValue->setStringValue($value->getStringValue());

            foreach ($value->getValues() as $itemValue) {
                $productValue->addValue($itemValue);
            }

            $target->addValue($productValue);
        }

        return true;
    }

    public function findSource(?int $sourceId): ?RozetkaProduct
    {
        if ($sourceId === null || $sourceId <= 0) {
            return null;
        }

        return $this->rozetkaProductRepository->find($sourceId);
    }
}
