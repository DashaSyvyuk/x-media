<?php

namespace App\Admin\Controller;

use App\Entity\Product;
use App\Entity\ProductCharacteristic;
use App\Entity\ProductFilterAttribute;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends CRUDController
{
    public function cloneAction($id)
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $id));
        }

        /**
         * @var Product $clonedObject
         */
        $clonedObject = clone $object;

        $clonedObject->setTitle('Copy of ' . $object->getTitle());

        if ($object->getCharacteristics()) {
            foreach($object->getCharacteristics() as $characteristic) {
                $productCharacteristic = new ProductCharacteristic();
                $productCharacteristic->setTitle($characteristic->getTitle());
                $productCharacteristic->setValue($characteristic->getValue());
                $productCharacteristic->setPosition($characteristic->getPosition());

                $clonedObject->addCharacteristic($productCharacteristic);
            }
        }

        if ($object->getFilterAttributes()) {
            foreach ($object->getFilterAttributes() as $filterAttribute) {
                $productFilterAttribute = new ProductFilterAttribute();
                $productFilterAttribute->setFilter($filterAttribute->getFilter());
                $productFilterAttribute->setFilterAttribute($filterAttribute->getFilterAttribute());

                $clonedObject->addFilterAttribute($productFilterAttribute);
            }
        }

        $this->admin->create($clonedObject);

        $this->addFlash('sonata_flash_success', 'Cloned successfully');

        return new RedirectResponse($this->admin->generateUrl('list'));
    }
}
